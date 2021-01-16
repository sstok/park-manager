<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form\Type\User\Admin;

use ParkManager\Application\Command\User\GrantUserRole;
use ParkManager\Application\Command\User\RevokeUserRole;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Tests\UI\Web\Form\MessageFormTestCase;
use ParkManager\UI\Web\Form\Type\User\Admin\UserSecurityLevelForm;

/**
 * @internal
 */
final class UserSecurityLevelFormTest extends MessageFormTestCase
{
    protected static function getCommandName(): string
    {
        return '*';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandHandler = static fn () => null;
    }

    protected function getTypes(): array
    {
        return [
            $this->getMessageType(),
        ];
    }

    /**
     * @test
     * @dataProvider provideUnchangedLevelUsers
     */
    public function no_dispatch_when_level_remains_unchanged(User $user, string $newLevel): void
    {
        $form = $this->factory->create(UserSecurityLevelForm::class, null, ['user' => $user]);
        $form->submit(['level' => $newLevel]);

        self::assertNull($this->dispatchedCommand);
    }

    public function provideUnchangedLevelUsers(): iterable
    {
        yield [User::register(UserId::create(), new EmailAddress('janE@example.com'), 'J', 'nope'), 'ROLE_USER'];
        yield [User::registerAdmin(UserId::create(), new EmailAddress('janE@example.com'), 'J', 'nope'), 'ROLE_ADMIN'];
        yield [$this->createSuperAdmin(), 'ROLE_SUPER_ADMIN'];
    }

    /**
     * @test
     * @dataProvider provideChangedUserLevel
     */
    public function dispatches_when_new_level_is_different(User $user, string $newLevel, object $expectedCommand): void
    {
        $form = $this->factory->create(UserSecurityLevelForm::class, null, ['user' => $user]);
        $form->submit(['level' => $newLevel]);

        self::assertEquals($expectedCommand, $this->dispatchedCommand);
    }

    public function provideChangedUserLevel(): iterable
    {
        // Revoke
        yield 'Revoke SUPER_ADMIN, to admin' => [$user = $this->createSuperAdmin(), 'ROLE_ADMIN', new RevokeUserRole($user->id, 'ROLE_SUPER_ADMIN')];
        yield 'Revoke SUPER_ADMIN, ADMIN, to user' => [$user = $this->createSuperAdmin(), 'ROLE_USER', new RevokeUserRole($user->id, 'ROLE_SUPER_ADMIN', 'ROLE_ADMIN')];
        yield 'Revoke ADMIN, to user' => [User::registerAdmin($id = UserId::create(), new EmailAddress('janE@example.com'), 'J', 'nope'), 'ROLE_USER', new RevokeUserRole($id, 'ROLE_SUPER_ADMIN', 'ROLE_ADMIN')];

        // Grant
        yield 'Grant ADMIN to User' => [User::register($id = UserId::create(), new EmailAddress('janE@example.com'), 'J', 'nope'), 'ROLE_ADMIN', new GrantUserRole($id, 'ROLE_ADMIN')];
        yield 'Grant ADMIN, SUPER_ADMIN to User' => [User::register($id = UserId::create(), new EmailAddress('janE@example.com'), 'J', 'nope'), 'ROLE_SUPER_ADMIN', new GrantUserRole($id, 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN')];
        yield 'Grant SUPER_ADMIN to Admin' => [User::registerAdmin($id = UserId::create(), new EmailAddress('janE@example.com'), 'J', 'nope'), 'ROLE_SUPER_ADMIN', new GrantUserRole($id, 'ROLE_SUPER_ADMIN')];
    }

    private function createSuperAdmin(): User
    {
        $userAdmin = User::registerAdmin(UserId::fromString('116b9495-d31f-4231-94b6-9580c5cedc1d'), new EmailAddress('janE@example.com'), 'J', 'nope');
        $userAdmin->addRole('ROLE_SUPER_ADMIN');

        return $userAdmin;
    }
}
