<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\User;

use ParkManager\Application\Command\User\DeleteRegistration;
use ParkManager\Application\Command\User\DeleteRegistrationHandler;
use ParkManager\Application\Service\OwnershipUsageList;
use ParkManager\Domain\Owner;
use ParkManager\Domain\User\Exception\CannotRemoveActiveUser;
use ParkManager\Domain\User\Exception\CannotRemoveSuperAdministrator;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DeleteRegistrationHandlerTest extends TestCase
{
    private const USER_ID1 = 'dba1f6a0-3c5e-4cc2-9d10-2b8ddf3ce605';
    private const USER_ID2 = '0667501e-a64f-4999-af58-9ad5c33fbca3';
    private const USER_ID3 = 'c9160f38-5c3b-4eed-bf50-03826bb7ebcb';

    private UserRepositoryMock $repository;
    private DeleteRegistrationHandler $handler;

    protected function setUp(): void
    {
        $superAdmin = UserRepositoryMock::createUser(email: 'admin@example.net', id: self::USER_ID3);
        $superAdmin->addRole('ROLE_ADMIN');
        $superAdmin->addRole('ROLE_SUPER_ADMIN');

        $this->repository = new UserRepositoryMock([
            $user = UserRepositoryMock::createUser(id: self::USER_ID1),
            UserRepositoryMock::createUser(email: 'nope@example.net', id: self::USER_ID2),
            $superAdmin,
        ]);
        $owner = Owner::byUser($user);

        $ownershipUsageList = new OwnershipUsageList([
            'space' => new SpaceRepositoryMock([SpaceRepositoryMock::createSpace(owner: $owner)]),
        ]);

        $this->handler = new DeleteRegistrationHandler($this->repository, $ownershipUsageList);
    }

    /** @test */
    public function it_deletes_a_user_registration(): void
    {
        ($this->handler)(new DeleteRegistration(self::USER_ID2));

        $this->repository->assertEntitiesWereRemoved([self::USER_ID2]);
    }

    /** @test */
    public function it_rejects_deletion_when_user_is_still_active(): void
    {
        $this->expectException(CannotRemoveActiveUser::class);

        ($this->handler)(new DeleteRegistration(self::USER_ID1));
    }

    /** @test */
    public function it_rejects_deletion_when_user_is_super_admin(): void
    {
        $this->expectException(CannotRemoveSuperAdministrator::class);

        ($this->handler)(new DeleteRegistration(self::USER_ID3));
    }
}
