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
use ParkManager\UI\Web\Form\DataMapper\PropertyPathObjectMapper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MessageFormType extends AbstractType
{
    private MessageBusInterface $messageBus;
    private TranslatorInterface $translator;
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(MessageBusInterface $messageBus, TranslatorInterface $translator, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->messageBus = $messageBus;
        $this->translator = $translator;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Explicitly set this to null to prevent mismatching of the modelData.
        // The Forms receives an object, but the actual modelData is an array.
        $resolver->setDefault('data_class', null);

        $resolver->setRequired(['command_factory']);
        $resolver->setDefault('disable_entity_mapping', false);
        $resolver->setDefault('exception_mapping', []);
        $resolver->setDefault('exception_fallback', null);

        $resolver->setAllowedTypes('command_factory', ['callable']);
        $resolver->setAllowedTypes('disable_entity_mapping', ['bool']);
        $resolver->setAllowedTypes('exception_mapping', ['callable[]']);
        $resolver->setAllowedTypes('exception_fallback', ['callable', 'null']);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (! $options['disable_entity_mapping']) {
            // Caution: This should be always executed as last!
            $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
                $event->setData(['model' => $event->getData(), 'fields' => []]);
            }, -1024);

            // Set a DataMapper to read from the 'model' key and write to the 'fields' key.
            // The DataMapper always updates on the form modelData, meaning we can call getData()
            // and get the actual model and fields.
            //
            // Note that only changed values are actually set in the 'fields' array, which makes it
            // possible to handle very special cases. Including not dispatching a Command et-all.
            //
            // For very custom forms set a custom DataMapper (wrapped by the `CommandDataMapper`).
            $builder->setDataMapper(new CommandDataMapper(new PropertyPathObjectMapper($this->propertyAccessor)));
        }

        // After all operations, including validation.
        // The TransformationFailureExtension has a priority of 1024
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($options): void {
            $form = $event->getForm();

            if ($form->getTransformationFailure() === null && $form->isValid()) {
                $data = $form->getData();

                if ($options['disable_entity_mapping']) {
                    $command = $options['command_factory']($data);
                } else {
                    $command = $options['command_factory']($data['fields'], $data['model']);
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
        } catch (HandlerFailedException $e) {
            $e = \current($e->getNestedExceptions());

            if ($e === false) {
                return;
            }

            $exceptionName = \get_class($e);

            if (isset($exceptionMapping[$exceptionName])) {
                $errors = $exceptionMapping[$exceptionName]($e, $this->translator, $form);
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
