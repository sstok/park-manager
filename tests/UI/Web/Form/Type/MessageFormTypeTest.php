<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form\Type;

use Exception;
use InvalidArgumentException;
use ParkManager\Tests\UI\Web\Form\Type\Mocks\StubCommand;
use ParkManager\UI\Web\Form\Type\MessageFormType;
use RuntimeException;
use Symfony\Component\Form\Exception\RuntimeException as FormRuntimeException;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * @internal
 */
final class MessageFormTypeTest extends TypeTestCase
{
    private ?StubCommand$dispatchedCommand = null;

    protected function getTypes(): array
    {
        $messageBus = $this->createMessageBus([
            StubCommand::class => [
                'stub-handler' => function (StubCommand $command): void {
                    if ($command->id === 3) {
                        throw new FormRuntimeException('I have no idea how this happened.');
                    }

                    if ($command->id === 5) {
                        throw new InvalidArgumentException('Invalid id provided.');
                    }

                    if ($command->id === 6) {
                        throw new RuntimeException('What is that awful smell?');
                    }

                    if ($command->id === 42) {
                        throw new Exception('You know nothing');
                    }

                    $this->dispatchedCommand = $command;
                },
            ],
        ]);

        return [
            new MessageFormType($messageBus, new IdentityTranslator()),
        ];
    }

    private function createMessageBus(array $handlers, bool $allowNoHandlers = false): MessageBus
    {
        return new MessageBus([new HandleMessageMiddleware(new HandlersLocator($handlers), $allowNoHandlers)]);
    }

    /** @test */
    public function it_does_not_dispatch_unless_submitted(): void
    {
        $form = $this->createFormForCommand();

        self::assertNull($form->getTransformationFailure());
        self::assertNull($this->dispatchedCommand);
    }

    private function createFormForCommand(): FormInterface
    {
        $profileContactFormType = $this->factory->createNamedBuilder('contact')
            ->add('email', TextType::class, ['required' => false])
            ->add('address', TextType::class, ['required' => false]);

        $profileFormType = $this->factory->createNamedBuilder('profile')
            ->add('name', TextType::class, ['required' => false])
            ->add($profileContactFormType);

        return $this->factory->createNamedBuilder('register_user', MessageFormType::class, null, [
            'command_factory' => static fn (array $data): StubCommand => new StubCommand($data['id'], $data['username'], $data['profile'] ?? null),
            'exception_mapping' => [
                FormRuntimeException::class => static fn (Throwable $e) => new FormError('Root problem is here', null, [], null, $e),
                InvalidArgumentException::class => static fn (Throwable $e, TranslatorInterface $translator) => ['id' => new FormError($translator->trans($e->getMessage()), null, [], null, $e)],
                RuntimeException::class => static fn (Throwable $e) => [
                    null => [new FormError('Root problem is here2', null, [], null, $e)],
                    'username' => [new FormError('Username problem is here', null, [], null, $e)],
                ],
            ],
            'exception_fallback' => static fn (Throwable $e, TranslatorInterface $translator) => [
                'profile.contact.email' => new FormError($translator->trans('Contact Email problem is here'), null, [], null, $e),
            ],
        ])
            ->add('id', IntegerType::class, ['required' => false])
            ->add('username', TextType::class, ['required' => false])
            ->add($profileFormType)
            ->getForm();
    }

    /**
     * @param array<FormError[]> $expectedErrors
     *
     * @test
     * @dataProvider provideExceptions
     */
    public function it_handles_errors_thrown_during_dispatching($id, array $expectedErrors): void
    {
        $form = $this->createFormForCommand();
        $form->submit(['id' => $id, 'username' => 'Nero']);

        self::assertFalse($form->isValid());
        self::assertNull($form->getTransformationFailure());
        self::assertNull($this->dispatchedCommand);

        foreach ($expectedErrors as $formPath => $formErrors) {
            $formPath = (string) $formPath;
            $currentForm = $form;

            if ($formPath !== '') {
                foreach (\explode('.', $formPath) as $child) {
                    $currentForm = $currentForm->get($child);
                }
            }

            /** @var FormError $error */
            foreach ($formErrors as $error) {
                $error->setOrigin($currentForm);
            }

            self::assertEquals($formErrors, [...$currentForm->getErrors()]);
        }
    }

    public static function provideExceptions(): iterable
    {
        yield 'root form error' => [
            3,
            [
                null => [new FormError('Root problem is here', null, [], null, new FormRuntimeException('I have no idea how this happened.'))],
            ],
        ];

        yield 'sub form' => [
            5,
            [
                'id' => [new FormError('Invalid id provided.', null, [], null, new InvalidArgumentException('Invalid id provided.'))],
            ],
        ];

        yield 'sub form 2' => [
            6,
            [
                null => [new FormError('Root problem is here2', null, [], null, new RuntimeException('What is that awful smell?'))],
                'username' => [new FormError('Username problem is here', null, [], null, new RuntimeException('What is that awful smell?'))],
            ],
        ];

        yield 'fallback for form' => [
            42,
            [
                'profile.contact.email' => [new FormError('Contact Email problem is here', null, [], null, new Exception('You know nothing'))],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_ignores_unmapped_exceptions_thrown_during_dispatching(): void
    {
        $form = $this->factory->createNamedBuilder('register_user', MessageFormType::class, null, [
            'command_factory' => static fn (array $data): StubCommand => new StubCommand($data['id'], $data['username'], $data['profile'] ?? null),
            'exception_mapping' => [
                FormRuntimeException::class => static fn (Throwable $e) => new FormError('Root problem is here', null, [], null, $e),
            ],
        ])
            ->add('id', IntegerType::class, ['required' => false])
            ->add('username', TextType::class, ['required' => false])
            ->getForm();

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('You know nothing');

        $form->submit(['id' => 42, 'username' => 'Nero']);
    }

    /** @test */
    public function it_dispatches_a_command(): void
    {
        $form = $this->createFormForCommand();
        $form->submit(['id' => '8', 'username' => 'Nero']);

        self::assertTrue($form->isValid());
        self::assertNull($form->getTransformationFailure());
        self::assertEquals(new StubCommand(8, 'Nero', [
            'name' => null,
            'contact' => [
                'email' => null,
                'address' => null,
            ],
        ]), $this->dispatchedCommand);
    }
}
