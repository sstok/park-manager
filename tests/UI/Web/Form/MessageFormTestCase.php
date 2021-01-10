<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form;

use ParkManager\UI\Web\Form\Type\MessageFormType;
use ParkManager\UI\Web\Form\Type\ViolationMapper;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ValidatorBuilder;

abstract class MessageFormTestCase extends TypeTestCase
{
    protected ?object $dispatchedCommand = null;

    /** @var callable|null */
    protected $commandHandler;

    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension((new ValidatorBuilder())->getValidator()),
        ];
    }

    protected function getMessageType(): MessageFormType
    {
        $handlers = [
            static::getCommandName() => [
                'handler' => function (object $command): void {
                    if (\is_callable($this->commandHandler)) {
                        ($this->commandHandler)($command);
                        $this->dispatchedCommand = $command;

                        return;
                    }

                    $this->fail('The "commandHandler" property must be set with valid callable.');
                },
            ],
        ];
        $messageBus = new MessageBus([new HandleMessageMiddleware(new HandlersLocator($handlers), false)]);

        $translator = new IdentityTranslator();
        $formRenderer = $this->createMock(FormRendererInterface::class);
        $formRenderer
            ->method('humanize')
            ->willReturnCallback(
                static fn (string $text): string => \ucfirst(
                    \mb_strtolower(\trim(\preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $text)))
                )
            );

        return new MessageFormType($messageBus, $translator, new ViolationMapper($translator, $formRenderer));
    }

    abstract protected static function getCommandName(): string;

    /**
     * @param array<string|null,FormError[]> $expectedErrors
     */
    protected function assertFormHasErrors(FormInterface $form, iterable $expectedErrors): void
    {
        static::assertGreaterThan(0, \count($form->getErrors(true)));
        static::assertNull($form->getTransformationFailure());
        static::assertNull($this->dispatchedCommand);

        foreach ($expectedErrors as $formPath => $formErrors) {
            $formPath = (string) $formPath;
            $currentForm = $form;

            if ($formPath !== '' && ! \ctype_digit($formPath)) {
                foreach (\explode('.', $formPath) as $child) {
                    $currentForm = $currentForm->get($child);
                }
            }

            static::assertThat($currentForm->getErrors(), new IsFormErrorsEqual($formErrors));
        }
    }

    protected static function assertFormIsValid(FormInterface $form): void
    {
        if (! $form->isValid()) {
            $errors = '';

            foreach ($form->getErrors(true) as $error) {
                $cause = $error->getCause();
                $causeStr = '';

                if ($cause instanceof ConstraintViolationInterface) {
                    $causeStr .= '(ConstraintViolation)' . $cause->getMessage();
                    $cause = \method_exists($cause, 'getCause') ? $cause->getCause() : null;
                }

                if ($cause instanceof \Exception) {
                    $causeStr .= $cause->getMessage() . "\n" . $cause->getTraceAsString();
                }

                $errors .= \sprintf(
                    "Message: %s\nOrigin: %s\nCause: %s\n==========================================\n\n",
                    $error->getMessage(),
                    \is_object($error->getOrigin()) ? $error->getOrigin()->getName() : 'null',
                    $causeStr
                );
            }

            static::fail("Form contains unexpected errors: \n\n" . $errors);
        }

        static::assertTrue($form->isValid());
    }
}
