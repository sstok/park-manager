<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form\Type\User;

use ParkManager\Application\Command\Administrator\RegisterAdministrator;
use ParkManager\Application\Command\User\RegisterUser;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\User\UserId;
use ParkManager\Tests\UI\Web\Form\MessageFormTestCase;
use ParkManager\Tests\UI\Web\Form\Type\Mocks\FakePasswordHasherFactory;
use ParkManager\UI\Web\Form\Type\EmailTypeDomainValueExtension;
use ParkManager\UI\Web\Form\Type\Security\SecurityUserHashedPasswordType;
use ParkManager\UI\Web\Form\Type\User\RegisterUserForm;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * @internal
 */
final class RegisterUserFormTest extends MessageFormTestCase
{
    protected static function getCommandName(): string
    {
        return '*';
    }

    /**
     * @return FormTypeInterface[]
     */
    protected function getTypes(): array
    {
        return [
            $this->getMessageType(),
            new SecurityUserHashedPasswordType(new FakePasswordHasherFactory()),
        ];
    }

    /**
     * @return FormTypeExtensionInterface[]
     */
    protected function getTypeExtensions()
    {
        return [
            new EmailTypeDomainValueExtension(),
        ];
    }

    /** @test */
    public function register_user_with_valid_information(): void
    {
        $this->commandHandler = static fn () => null;

        $form = $this->factory->create(RegisterUserForm::class, null, ['user_id' => $id = UserId::fromString('fb13df5a-9ce7-413c-99a1-ae5eb3d642bd')]);
        $form->submit([
            'display_name' => 'Terry Bell',
            'email' => 'terry.bell9@example.com',
            'password' => [
                'password' => '3FNU@7Jg',
            ],
        ]);

        self::assertFormIsValid($form);
        self::assertEquals(
            (new RegisterUser(
                $id,
                new EmailAddress('terry.bell9@example.com'),
                'Terry Bell',
                'encoded(3FNU@7Jg)'
            ))->requireNewPassword(),
            $this->dispatchedCommand
        );
    }

    /** @test */
    public function register_administrator_with_valid_information(): void
    {
        $this->commandHandler = static fn () => null;

        $form = $this->factory->create(RegisterUserForm::class, null, ['user_id' => $id = UserId::fromString('fb13df5a-9ce7-413c-99a1-ae5eb3d642bd')]);
        $form->submit([
            'display_name' => 'Terry Bell',
            'email' => 'terry.bell9@example.com',
            'password' => [
                'password' => '3FNU@7Jg',
            ],
            'is_admin' => true,
        ]);

        self::assertFormIsValid($form);
        self::assertEquals(
            (new RegisterAdministrator(
                $id,
                new EmailAddress('terry.bell9@example.com'),
                'Terry Bell',
                'encoded(3FNU@7Jg)'
            ))->requireNewPassword(),
            $this->dispatchedCommand
        );
    }
}
