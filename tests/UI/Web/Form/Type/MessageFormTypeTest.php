<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form\Type;

use Exception;
use Generator;
use InvalidArgumentException;
use ParkManager\Domain\Exception\InvalidSplitTokenProvided;
use ParkManager\Domain\User\Exception\UserNotFound;
use ParkManager\Domain\User\UserId;
use ParkManager\Tests\UI\Web\Form\IsFormErrorsEqual;
use ParkManager\Tests\UI\Web\Form\Type\Mocks\StubCommand;
use ParkManager\UI\Web\Form\Type\MessageFormType;
use ParkManager\UI\Web\Form\Type\ViolationMapper;
use RuntimeException;
use stdClass;
use Symfony\Component\Form\Exception\InvalidArgumentException as FormInvalidArgumentException;
use Symfony\Component\Form\Exception\RuntimeException as FormRuntimeException;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use ValueError;

/**
 * @internal
 */
final class MessageFormTypeTest extends TypeTestCase
{
    private ?StubCommand$dispatchedCommand = null;

    /**
     * @return FormTypeInterface[]
     */
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

                    if ($command->id === 66) {
                        throw new InvalidSplitTokenProvided();
                    }

                    if ($command->id === 77) {
                        throw UserNotFound::withId(UserId::fromString('f2df40e4-2f27-47e8-b03f-27d1456eed7a'));
                    }

                    $this->dispatchedCommand = $command;
                },
            ],
        ]);

        $translator = new IdentityTranslator();
        $formRenderer = $this->createMock(FormRendererInterface::class);
        $formRenderer
            ->method('humanize')
            ->willReturnCallback(
                static fn (string $text): string => ucfirst(mb_strtolower(trim(preg_replace(['/([A-Z])/', '/[_\s]+/'], ['_$1', ' '], $text))))
            )
        ;

        return [
            new MessageFormType($messageBus, $translator, new ViolationMapper($translator, $formRenderer)),
        ];
    }

    /**
     * @param array<class-string, callable[]> $handlers
     */
    private function createMessageBus(array $handlers): MessageBus
    {
        $validationMiddleware = new class() implements MiddlewareInterface {
            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                $message = $envelope->getMessage();

                if ($message instanceof StubCommand && $message->id === 534) {
                    throw new ValidationFailedException(
                        $message,
                        new ConstraintViolationList([
                            new ConstraintViolation('This value is not like the others.', 'This value is not like the others.', [], $message, '', 'Beer Me'),
                            new ConstraintViolation('Well yes, but actually no.', 'Well yes, but actually no.', [], $message, 'profile.name', '9848829240'),
                            new ConstraintViolation('Lavara Cadabra {{ label }}', 'Lavara {{ label }}', [], $message, 'is_admin', 'nope'),
                            new ConstraintViolation('Locus phopocus {{ label }}', 'Locus phopocus {{ label }}', [], $message, 'sure_name', 'nope'),
                        ])
                    );
                }

                return $stack->next()->handle($envelope, $stack);
            }
        };

        $exceptionAtMiddleware = new class() implements MiddlewareInterface {
            public function handle(Envelope $envelope, StackInterface $stack): Envelope
            {
                $message = $envelope->getMessage();

                if ($message instanceof StubCommand && $message->id === 725) {
                    throw new ValueError('It works on my machine');
                }

                return $stack->next()->handle($envelope, $stack);
            }
        };

        return new MessageBus([$validationMiddleware, $exceptionAtMiddleware, new HandleMessageMiddleware(new HandlersLocator($handlers), false)]);
    }

    /** @test */
    public function it_does_not_dispatch_unless_submitted(): void
    {
        $form = $this->createFormForCommand();

        self::assertNull($form->getTransformationFailure());
        self::assertNull($this->dispatchedCommand);
    }

    /**
     * @param MessageFormTypeEntity|array<string, mixed>|null $data
     */
    private function createFormForCommand(MessageFormTypeEntity | array | null $data = null, bool $withFallback = true): FormInterface
    {
        $profileContactFormType = $this->factory->createNamedBuilder('contact')
            ->add('email', TextType::class, ['required' => false])
            ->add('address', TextType::class, ['required' => false])
        ;

        $profileFormType = $this->factory->createNamedBuilder('profile')
            ->add('name', TextType::class, ['required' => false])
            ->add('full_name', TextType::class, ['required' => false, 'label_format' => 'My "%id%" is name "%name%"'])
            ->add($profileContactFormType)
        ;

        $options = [
            'command_factory' => static fn (array $data): StubCommand => new StubCommand($data['id'], $data['username'], $data['profile'] ?? null),
            'exception_mapping' => [
                FormRuntimeException::class => static fn (Throwable $e) => new FormError('Root problem is here', null, [], null, $e),
                InvalidArgumentException::class => static fn (Throwable $e, TranslatorInterface $translator) => ['id' => new FormError($translator->trans($e->getMessage()), null, [], null, $e)],
                RuntimeException::class => static fn (Throwable $e) => [
                    null => [new FormError('Root problem is here2', null, [], null, $e)],
                    'username' => [new FormError('Username problem is here', null, [], null, $e)],
                ],
                InvalidSplitTokenProvided::class => 'profile.name',
                ValueError::class => static fn (Throwable $e) => new FormError($e->getMessage(), null, [], null, $e),
            ],
            'exception_fallback' => static fn (Throwable $e, TranslatorInterface $translator) => [
                'profile.contact.email' => new FormError($translator->trans(/** @Ignore */ 'Contact Email problem is here'), null, [], null, $e),
            ],
            'violation_mapping' => [
                'is_admin' => 'profile.full_name',
                'sure_name' => '',
            ],
        ];

        if (! $withFallback) {
            unset($options['exception_fallback']);
        }

        return $this->factory->createNamedBuilder('register_user', MessageFormType::class, $data, $options)
            ->add('id', IntegerType::class, ['required' => false])
            ->add('username', TextType::class, ['required' => false])
            ->add($profileFormType)
            ->getForm()
        ;
    }

    /**
     * @param array<string|null, FormError[]> $expectedErrors
     *
     * @test
     * @dataProvider provideExceptions
     */
    public function it_handles_errors_thrown_during_dispatching(int $id, array $expectedErrors): void
    {
        $form = $this->createFormForCommand(null, $id !== 77 && $id !== 534);
        $form->submit(['id' => $id, 'username' => 'Nero']);

        self::assertFalse($form->isValid());
        self::assertNull($form->getTransformationFailure());
        self::assertNull($this->dispatchedCommand);

        foreach ($expectedErrors as $formPath => $formErrors) {
            $formPath = (string) $formPath;
            $currentForm = $form;

            if ($formPath !== '') {
                foreach (explode('.', $formPath) as $child) {
                    $currentForm = $currentForm->get($child);
                }
            }

            foreach ($formErrors as $error) {
                $error->setOrigin($currentForm);
            }

            self::assertThat([...$currentForm->getErrors()], new IsFormErrorsEqual($formErrors));
        }
    }

    /**
     * @return Generator<string, array{0: int, 1: array<string|null, mixed>}>
     */
    public static function provideExceptions(): Generator
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

        yield 'TranslatableException' => [
            77,
            [
                '' => [new FormError('User with id "f2df40e4-2f27-47e8-b03f-27d1456eed7a" does not exist.', 'User with id "{id}" does not exist.', ['{id}' => $userId = UserId::fromString('f2df40e4-2f27-47e8-b03f-27d1456eed7a')], null, UserNotFound::withId($userId))],
            ],
        ];

        yield 'Exception outside of handler (in middleware)' => [
            725,
            [
                '' => [new FormError('It works on my machine', cause: new ValueError('It works on my machine'))],
            ],
        ];

        yield 'ValidationFailedException' => [
            534,
            [
                '' => [
                    new FormError('This value is not like the others.', null),
                    new FormError('Locus phopocus Register user', null),
                ],
                'profile.full_name' => [
                    new FormError('Lavara Cadabra My "[full_name]" is name "full_name"', 'Lavara My "[full_name]" is name "full_name"'),
                ],
                'profile.name' => [new FormError('Well yes, but actually no.')],
            ],
        ];

        yield 'Exception mapped to form-path' => [
            66,
            [
                'profile.name' => [new FormError('invalid_split_token')],
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
            ->getForm()
        ;

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('You know nothing');

        $form->submit(['id' => 42, 'username' => 'Nero']);
    }

    /** @test */
    public function it_handles_an_entity_as_data(): void
    {
        $form = $this->createFormForCommand(new MessageFormTypeEntity());

        self::assertSame(50, $form->get('id')->getData());
        self::assertSame('Bernard', $form->get('username')->getData());
        self::assertNull($form->get('profile')->getData());
    }

    /** @test */
    public function it_validates_model_type(): void
    {
        $options = [
            'command_factory' => static fn (array $data): StubCommand => new StubCommand($data['id'], $data['username'], $data['profile'] ?? null),
            'model_class' => stdClass::class,
        ];

        $this->expectException(FormInvalidArgumentException::class);
        $this->expectExceptionMessage('Expected model class of type "stdClass". But "' . MessageFormTypeEntity::class . '" was given for "register_user".');

        $this->factory->createNamedBuilder('register_user', MessageFormType::class, new MessageFormTypeEntity(), $options)
            ->add('id', IntegerType::class, ['required' => false])
            ->add('username', TextType::class, ['required' => false])
            ->getForm()
        ;
    }

    /** @test */
    public function it_validates_model_type_multiple(): void
    {
        $options = [
            'command_factory' => static fn (array $data): StubCommand => new StubCommand($data['id'], $data['username'], $data['profile'] ?? null),
            'model_class' => [stdClass::class, StubCommand::class],
        ];

        $this->expectException(FormInvalidArgumentException::class);
        $this->expectExceptionMessage('Expected model class of type "stdClass", "' . StubCommand::class . '". But "' . MessageFormTypeEntity::class . '" was given for "register_user".');

        $this->factory->createNamedBuilder('register_user', MessageFormType::class, new MessageFormTypeEntity(), $options)
            ->add('id', IntegerType::class, ['required' => false])
            ->add('username', TextType::class, ['required' => false])
            ->getForm()
        ;
    }

    /** @test */
    public function it_dispatches_a_command_with_an_array_init_data(): void
    {
        $form = $this->createFormForCommand(['id' => '9', 'username' => 'Dio']);

        self::assertSame('9', $form->get('id')->getData());
        self::assertSame('Dio', $form->get('username')->getData());
        self::assertNull($form->get('profile')->getData());

        $form->submit(['id' => '8', 'username' => 'Nero']);

        self::assertTrue($form->isValid());
        self::assertNull($form->getTransformationFailure());
        self::assertEquals(new StubCommand(8, 'Nero', [
            'name' => null,
            'full_name' => null,
            'contact' => [
                'email' => null,
                'address' => null,
            ],
        ]), $this->dispatchedCommand);

        self::assertSame(8, $form->get('id')->getData());
        self::assertSame('Nero', $form->get('username')->getData());
        self::assertSame(
            [
                'name' => null,
                'full_name' => null,
                'contact' => [
                    'email' => null,
                    'address' => null,
                ],
            ],
            $form->get('profile')->getData()
        );
    }

    /** @test */
    public function it_dispatches_a_command(): void
    {
        $form = $this->createFormForCommand(new MessageFormTypeEntity());
        $form->submit(['id' => '8', 'username' => 'Nero']);

        self::assertTrue($form->isValid());
        self::assertNull($form->getTransformationFailure());
        self::assertEquals(new StubCommand(8, 'Nero', [
            'name' => null,
            'full_name' => null,
            'contact' => [
                'email' => null,
                'address' => null,
            ],
        ]), $this->dispatchedCommand);
    }
}

/**
 * @internal
 */
final class MessageFormTypeEntity
{
    public int $id = 50;
    public string $username = 'Bernard';
    public mixed $profile = null;
}
