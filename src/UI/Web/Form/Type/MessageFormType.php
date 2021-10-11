<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type;

use Closure;
use ParkManager\Domain\Exception\DomainError;
use ParkManager\UI\Web\Form\DataMapper\CommandDataMapper;
use ParkManager\UI\Web\Form\DataMapper\PropertyPathObjectAccessor;
use ParkManager\UI\Web\Form\Model\CommandDto;
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
    private DataAccessorInterface $dataAccessor;
    private DataMapper $dataMapper;

    public function __construct(
        private MessageBusInterface $messageBus,
        private TranslatorInterface $translator,
        private ViolationMapper $violationMapper,
        ?PropertyAccessorInterface $propertyAccessor = null
    ) {
        $propertyAccessor ??= PropertyAccess::createPropertyAccessor();

        $this->dataAccessor = new ChainAccessor([
            new CallbackAccessor(),
            new PropertyPathObjectAccessor($propertyAccessor),
        ]);
        $this->dataMapper = new DataMapper($this->dataAccessor);
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
        $resolver->setAllowedTypes('exception_mapping', ['array']); // [exceptionName] => {callable | 'string | DomainError'}
        $resolver->setAllowedTypes('exception_fallback', ['callable', 'null']);
        $resolver->setAllowedTypes('violation_mapping', ['string[]']);
        $resolver->setNormalizer('model_class', static fn (Options $options, $value): ?array => $value !== null ? (array) $value : null);

        $resolver
            ->setInfo('model_class', 'The data-class expected to be provided as initial data. Do not set the "data_class" option!')
            ->setInfo('command_factory',
                'A callable used to generate a command message for the MessageBus. ' .
                'Prototype: (formData {CommandDto}, modelData {object|array}, form {FormInterface}) or (formData {array}, form {FormInterface}) when "disable_entity_mapping" option is `true`.'
            )
            ->setInfo('disable_entity_mapping', 'Disable mapping of entity-data to the form, either when no Model is provided or when mapping is not possible.')
            ->setInfo('exception_mapping',
                <<<'INFO'
                    Maps exceptions thrown during the message handling to either the root form (default) or the structure of sub-forms.

                    Must be an array with the exception class-name as key, and it's value be either a valid handler as described below, or an array with
                    one or more form paths and a valid handler.

                    A handler is either a translator message-id string, {\ParkManager\Domain\Exception\DomainError} object
                    or callable with prototype ({Exception}, TranslatorInterface, {FormInterface}) returning one or more {FormError} objects.

                    Examples handlers:

                      * `'message-id'` maps to the root-form with the FormError being a translated version of the message-id;
                      * `new DomainError()` maps to the root-form with the FormError being a translated version of the DomainError;
                      * `[null => 'message-id']` maps to the root-form with the FormError being a translated version of the message-id;
                      * `[null => new DomainError()]` maps to the root-form with the FormError being a translated version of the DomainError;
                      * `[null => fn ($e) => new FormError(...)]` maps to the root-form with the produced FormError;
                      * `[null => fn ($e) => [new FormError(...)]]` maps to the root-form with the produced FormError;
                      * `['profile.name' => 'message-id']` maps to the profile.name sub-form with the FormError being a translated version of the message-id;
                      * `['profile.name' => 'message-id', null => 'message-id']` maps the errors to their respective (sub) form;

                    When no mapping for the exception is found (in order):

                      * The "exception_fallback" handler is used.
                      * When the exception is a {DomainError} object map the error the root form.
                      * When all failed, the exception bubbles-up to the framework exception handler.

                    INFO
            )
            ->setInfo('exception_fallback', 'A callable used when no mapping was found the for exception-class name in "exception_mapping".')
            ->setInfo('violation_mapping', 'Map the property-path of a validator violation to a form-path (profile.name). Throws configuration error no mapping is found.')
        ;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (! $options['disable_entity_mapping']) {
            // Caution: This should be always executed as last!
            $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
                $acceptedModelClass = $event->getForm()->getConfig()->getOption('model_class');

                if ($acceptedModelClass !== null && ! \in_array($event->getData()::class, $acceptedModelClass, true)) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Expected model class of type "%s". But "%s" was given for "%s".',
                            implode('", "', $acceptedModelClass),
                            get_debug_type($event->getData()),
                            $event->getForm()->getName()
                        )
                    );
                }

                $event->setData(new CommandDto(model: $event->getData()));
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
                    $command = $options['command_factory']($data, $data->model, $form);
                }

                $this->dispatchCommand($command, $form, $options['exception_mapping'], $options['exception_fallback']);
            }
        }, -1050);
    }

    /**
     * @param array<string, string|Closure>                                  $exceptionMapping
     * @param callable(Throwable, TranslatorInterface, FormInterface): mixed $exceptionFallback
     */
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
                $e = current($e->getNestedExceptions());

                if ($e === false) {
                    return;
                }
            }

            $exceptionName = $e::class;

            if (isset($exceptionMapping[$exceptionName])) {
                $handlerOrPath = $exceptionMapping[$exceptionName];

                if (\is_string($handlerOrPath)) {
                    \assert($e instanceof DomainError);

                    $errors = [$handlerOrPath => $this->translatableExceptionToFormError($e)];
                } else {
                    $errors = $handlerOrPath($e, $this->translator, $form);
                }
            } elseif ($exceptionFallback !== null) {
                $errors = $exceptionFallback($e, $this->translator, $form);
            } elseif ($e instanceof DomainError) {
                $errors = [null => $this->translatableExceptionToFormError($e)];
            } else {
                throw $e;
            }

            $this->mapErrors($errors, $form);
        }
    }

    private function translatableExceptionToFormError(DomainError $e): FormError
    {
        $message = $e->getTranslatorMsg();

        if (\is_string($message)) {
            return new FormError(
                $this->translator->trans($message, [], 'validators'),
                $message,
                cause: $e
            );
        }

        $parameters = method_exists($message, 'getParameters') ? $message->getParameters() : [];
        \assert(method_exists($message, '__toString'));

        return new FormError(
            $message->trans($this->translator),
            (string) $message,
            $parameters,
            cause: $e
        );
    }

    /**
     * @param array<string|null, FormError>|FormError $errors
     */
    private function mapErrors(array | FormError $errors, FormInterface $form): void
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
                foreach (explode('.', $formPath) as $child) {
                    $form = $form->get($child);
                }
            }

            foreach ($formErrors as $error) {
                $form->addError($error);
            }
        }
    }
}
