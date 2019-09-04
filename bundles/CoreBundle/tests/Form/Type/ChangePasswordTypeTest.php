<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Tests\Form\Type;

use Closure;
use ParkManager\Bundle\CoreBundle\Form\Type\Security\ChangePasswordType;
use ParkManager\Bundle\CoreBundle\Security\ClientUser;
use ParkManager\Bundle\CoreBundle\Test\Infrastructure\UserInterface\Web\Form\TransformationFailureExtension;
use ParkManager\Bundle\CoreBundle\Tests\Form\Type\Mocks\FakePasswordHashFactory;
use Rollerworks\Bundle\MessageBusFormBundle\Test\MessageFormTestCase;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;

/**
 * @internal
 */
final class ChangePasswordTypeTest extends MessageFormTestCase
{
    use ValidatorExtensionTrait;

    /** @var FakePasswordHashFactory */
    private $encoderFactory;

    protected function getExtensions(): array
    {
        return [
            $this->getValidatorExtension(),
        ];
    }

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
        $this->encoderFactory = new FakePasswordHashFactory();

        parent::setUp();
    }

    protected function getTypes(): array
    {
        return [
            new ChangePasswordType($this->encoderFactory),
        ];
    }

    /** @test */
    public function it_hashes_password(): void
    {
        $form = $this->factory->create(ChangePasswordType::class, null, [
            'user_class' => ClientUser::class,
            'command_builder' => $this->getCommandBuilder(),
            'user_id' => 1,
        ]);
        $form->submit([
            'password' => ['password' => ['first' => 'Hello there', 'second' => 'Hello there']],
        ]);

        static::assertTrue($form->isValid());
        static::assertEquals(new ChangeUserPassword('1', 'encoded(Hello there)'), $form->getData());
    }

    /** @test */
    public function it_does_not_change_user_id(): void
    {
        $form = $this->factory->create(ChangePasswordType::class, null, [
            'user_class' => ClientUser::class,
            'command_builder' => $this->getCommandBuilder(),
            'user_id' => 1,
        ]);
        $form->submit([
            'password' => ['password' => ['first' => 'Hello there', 'second' => 'Hello there'], 'user_id' => '2'],
        ]);

        static::assertTrue($form->isValid());
        static::assertEquals(new ChangeUserPassword('1', 'encoded(Hello there)'), $form->getData());
    }

    /** @test */
    public function it_does_not_accept_invalid_input(): void
    {
        $form = $this->factory->create(ChangePasswordType::class, null, [
            'user_class' => ClientUser::class,
            'command_builder' => $this->getCommandBuilder(),
            'user_id' => 1,
        ]);
        $form->submit(['password' => 'Hello there']);

        static::assertEquals(new ChangeUserPassword('1', ''), $form->getData());
    }

    /** @test */
    public function it_gives_null_for_model_password(): void
    {
        $form = $this->factory->create(ChangePasswordType::class, null, [
            'user_class' => ClientUser::class,
            'command_builder' => $this->getCommandBuilder(),
            'user_id' => 1,
        ]);

        static::assertFalse($form->isSubmitted());
        static::assertNull($form->getData());
    }

    private function getCommandBuilder(): Closure
    {
        return static function ($token, $password) {
            return new ChangeUserPassword($token, $password);
        };
    }
}

class ChangeUserPassword
{
    /** @var string */
    public $id;

    /** @var string */
    public $password;

    public function __construct(string $id, string $password)
    {
        $this->id = $id;
        $this->password = $password;
    }
}
