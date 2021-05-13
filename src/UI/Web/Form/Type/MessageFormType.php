<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type;

use ParkManager\Domain\Exception\TranslatableException;
use ParkManager\UI\Web\Form\DataMapper\CommandDataMapper;
use ParkManager\UI\Web\Form\DataMapper\PropertyPathObjectAccessor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataAccessorInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Extension\Core\DataAccessor\CallbackAccessor;
use Symfony\Component\Form\Extension\Core\DataAccessor\ChainAccessor;
use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

final class MessageFormType extends AbstractType
{
    private MessageBusInterface $messageBus;
    private TranslatorInterface $translator;
    private DataAccessorInterface $dataAccessor;
    private DataMapper $dataMapper;
    private ViolationMapper $violationMapper;

    public function __construct(MessageBusInterface $messageBus, TranslatorInterface $translator, ViolationMapper $violationMapper, PropertyAccessorInterface $propertyAccessor = null)
    {
        $propertyAccessor ??= PropertyAccess::createPropertyAccessor();

        $this->messageBus = $messageBus;
        $this->translator = $translator;
        $this->dataAccessor = new ChainAccessor(
            [
                new CallbackAccessor(),
                new PropertyPathObjectAccessor($propertyAccessor),
            ]
        );
        $this->dataMapper = new DataMapper($this->dataAccessor);
        $this->violationMapper = $violationMapper;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Explicitly set this to null to prevent mismatching of the modelData.
        // The Forms receives an object, but the actual modelData is an array.
        $resolver->setDefault('data_class', null);
        $resolver->setDefault('model_class', null);

        $resolver->setRequired(['command_factory']);
        $resolver->setDefault('disable_entity_mapping', false);
        $resolver->setDefault('exception_mapping', []);
        $resolver->setDefault('exception_fallback', null);
        $resolver->setDefault('violation_mapping', []);

        $resolver->setAllowedTypes('command_factory', ['callable']);
        $resolver->setAllowedTypes('model_class', ['string', 'string[]', 'null']);
        $resolver->setAllowedTypes('disable_entity_mapping', ['bool']);
        $resolver->setAllowedTypes('exception_mapping', ['array']); // [exceptionName] => {callable | 'string'}
        $resolver->setAllowedTypes('exception_fallback', ['callable', 'null']);
        $resolver->setAllowedTypes('violation_mapping', ['string[]']);
        $resolver->setNormalizer('model_class', static fn (Options $options, $value): ?array => $value !== null ? (array) $value : null);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (! $options['disable_entity_mapping']) {
            // Caution: This should be always executed as last!
            $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
                $acceptedModelClass = $event->getForm()->getConfig()->getOption('model_class');

                if ($acceptedModelClass !== null && ! \in_array(\get_class($event->getData()), $acceptedModelClass, true)) {
                    throw new InvalidArgumentException(
                        \sprintf(
                            'Expected model class of type "%s". But "%s" was given for "%s".',
                            \implode('", "', $acceptedModelClass),
                            \get_debug_type($event->getData()),
                            $event->getForm()->getName()
                        )
                    );
                }

                $event->setData(['model' => $event->getData() ?? [], 'fields' => [], 'changed' => []]);
            }, -1024);

            // Set a DataMapper to read from the 'model' key and write to the 'fields' key.
            // The DataMapper always updates on the form modelData, meaning we can call getData()
            // and get the actual model and fields.
            //
            // Note that only changed values are set in the 'changed' array, which makes it
            // possible to handle very special cases. Including not dispatching a Command et-all.
            //
            // For very custom forms set a custom DataMapper (wrapped by the `CommandDataMapper`).
            $builder->setDataMapper(new CommandDataMapper($this->dataMapper, $this->dataAccessor));
        }

        // After all operations, including validation.
        // The TransformationFailureExtension has a priority of 1024
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($options): void {
            $form = $event->getForm();

            if ($form->getTransformationFailure() === null && $form->isValid()) {
                $data = $form->getData();

                if ($options['disable_entity_mapping']) {
                    $command = $options['command_factory']($data, $form);
                } else {
                    $command = $options['command_factory']($data['fields'], $data['model'], $form);
                }

                $this->dispatchCommand($command, $form, $options['exception_mapping'], $options['exception_fallback']);
            }
        }, -1050);
    }

    private function dispatchCommand(?object $command, FormInterface $form, array $exceptionMapping, ?callable $exceptionFallback): void
    {
        if ($command === null) {
            return;
        }

        try {
            $this->messageBus->dispatch($command);
        } catch (ValidationFailedException $e) {
            foreach ($e->getViolations() as $violation) {
                $this->violationMapper->mapViolation($violation, $form);
            }
        } catch (Throwable $e) {
            // It's still possible to exception was thrown at a middleware.
            if ($e instanceof HandlerFailedException) {
                $e = \current($e->getNestedExceptions());

                if ($e === false) {
                    return;
                }
            }

            $exceptionName = \get_class($e);

            if (isset($exceptionMapping[$exceptionName])) {
                $handlerOrPath = $exceptionMapping[$exceptionName];

                if (\is_string($handlerOrPath)) {
                    $errors = [$handlerOrPath => new FormError(
                        $this->translator->trans($e->getTranslatorId(), $e->getTranslationArgs(), 'validators'),
                        $e->getTranslatorId(),
                        $e->getTranslationArgs(),
                        null,
                        $e
                    )];
                } else {
                    $errors = $handlerOrPath($e, $this->translator, $form);
                }
            } elseif ($exceptionFallback !== null) {
                $errors = $exceptionFallback($e, $this->translator, $form);
            } elseif ($e instanceof TranslatableException) {
                $errors = [null => new FormError(
                    $this->translator->trans($e->getTranslatorId(), $e->getTranslationArgs(), 'validators'),
                    $e->getTranslatorId(),
                    $e->getTranslationArgs(),
                    null,
                    $e
                )];
            } else {
                throw $e;
            }

            $this->mapErrors($errors, $form);
        }
    }

    /**
     * @param array<string|null, FormError>|FormError $errors
     */
    private function mapErrors($errors, FormInterface $form): void
    {
        if (! \is_array($errors)) {
            $errors = [null => [$errors]];
        }

        foreach ($errors as $formPath => $formErrors) {
            if (! \is_array($formErrors)) {
                $formErrors = [$formErrors];
            }

            $formPath = (string) $formPath;

            if ($formPath !== '') {
                foreach (\explode('.', $formPath) as $child) {
                    $form = $form->get($child);
                }
            }

            foreach ($formErrors as $error) {
                $form->addError($error);
            }
        }
    }
}
