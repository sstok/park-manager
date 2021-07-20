<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form\Type;

use Closure;
use ParkManager\Tests\Form\TransformationFailureExtension;
use ParkManager\Tests\UI\Web\Form\MessageFormTestCase;
use ParkManager\Tests\UI\Web\Form\Type\Mocks\FakePasswordHasherFactory;
use ParkManager\UI\Web\Form\Model\CommandDto;
use ParkManager\UI\Web\Form\Type\Security\ChangePasswordType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;

/**
 * @internal
 */
final class ChangePasswordTypeTest extends MessageFormTestCase
{
    use ValidatorExtensionTrait;

    private FakePasswordHasherFactory $hasherFactory;

    /**
     * @return FormExtensionInterface[]
     */
    protected function getExtensions(): array
    {
        return [
            $this->getValidatorExtension(),
        ];
    }

    /**
     * @return FormTypeExtensionInterface[]
     */
    protected function getTypeExtensions(): array
    {
        return [
            new TransformationFailureExtension(),
        ];
    }

    protected static function getCommandName(): string
    {
        return ChangeUserPassword::class;
    }

    protected function setUp(): void
    {
        $this->commandHandler = static function (ChangeUserPassword $command): void { };
        $this->hasherFactory = new FakePasswordHasherFactory();

        parent::setUp();
    }

    /**
     * @return FormTypeInterface[]
     */
    protected function getTypes(): array
    {
        return [
            $this->getMessageType(),
            new ChangePasswordType($this->hasherFactory),
        ];
    }

    /** @test */
    public function it_hashes_password(): void
    {
        $form = $this->factory->create(ChangePasswordType::class, 1, [
            'command_factory' => $this->getCommandBuilder(),
        ]);
        $form->submit([
            'password' => ['password' => ['first' => 'Hello there', 'second' => 'Hello there']],
        ]);

        self::assertTrue($form->isValid());
        self::assertEquals(new ChangeUserPassword('1', 'encoded(Hello there)'), $this->dispatchedCommand);
    }

    /** @test */
    public function it_does_not_change_user_id(): void
    {
        $form = $this->factory->create(ChangePasswordType::class, 1, [
            'command_factory' => $this->getCommandBuilder(),
        ]);
        $form->submit([
            'password' => ['password' => ['first' => 'Hello there', 'second' => 'Hello there'], 'user_id' => '2'],
        ]);

        self::assertTrue($form->isValid());
        self::assertEquals(new ChangeUserPassword('1', 'encoded(Hello there)'), $this->dispatchedCommand);
    }

    /** @test */
    public function it_does_not_accept_invalid_input(): void
    {
        $form = $this->factory->create(ChangePasswordType::class, 1, [
            'command_factory' => $this->getCommandBuilder(),
        ]);
        $form->submit(['password' => 'Hello there']);

        self::assertFalse($form->isValid());
        self::assertNull($this->dispatchedCommand);
    }

    /** @test */
    public function it_gives_null_for_model_password(): void
    {
        $form = $this->factory->create(ChangePasswordType::class, 1, [
            'command_factory' => $this->getCommandBuilder(),
        ]);

        self::assertFalse($form->isSubmitted());
        self::assertNull($this->dispatchedCommand);
    }

    private function getCommandBuilder(): Closure
    {
        return static fn (CommandDto $data, array $model) => new ChangeUserPassword($model['id'], $data->fields['password']);
    }
}

/** @internal */
final class ChangeUserPassword
{
    public function __construct(public string $id, public string $password)
    {
    }
}
