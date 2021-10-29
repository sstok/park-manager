<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Organization;

use ParkManager\Domain\Organization\AccessLevel;
use ParkManager\Domain\Organization\Exception\OrganizationMemberNotFound;
use ParkManager\Domain\Organization\Organization;
use ParkManager\Domain\Organization\OrganizationId;
use ParkManager\Domain\Organization\OrganizationMember;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class OrganizationTest extends TestCase
{
    /** @test */
    public function its_constructable(): void
    {
        $org = new Organization(OrganizationId::create(), 'Test Organization Inc.');
        self::assertSame('Test Organization Inc.', $org->name);
        self::assertCount(0, $org->members);
        self::assertFalse($org->isInternal());

        $org = new Organization(OrganizationId::fromString(OrganizationId::ADMIN_ORG), 'Administrators');
        self::assertCount(0, $org->members);
        self::assertTrue($org->isInternal());

        $org = new Organization(OrganizationId::fromString(OrganizationId::SYSTEM_APP), 'SystemApplication');
        self::assertCount(0, $org->members);
        self::assertTrue($org->isInternal());
    }

    /** @test */
    public function allows_adding_members(): void
    {
        $user1 = UserRepositoryMock::createUser(email: 'jant@example.com', id: '5c9bbbab-4743-48e6-aadb-e0827be9476a');
        $user2 = UserRepositoryMock::createUser(email: 'brooklynn@example.com', id: 'da02351c-e519-4306-bade-72f4f22f6f8f');

        $org = new Organization(OrganizationId::create(), 'Test Organization Inc.');
        $org->addMember($user1);
        $org->addMember($user1); // No duplicate
        $org->addMember($user2, AccessLevel::get('LEVEL_COLLABORATOR'));

        self::assertEquals(
            [
                new OrganizationMember($user1, $org, AccessLevel::get('LEVEL_MANAGER')),
                new OrganizationMember($user2, $org, AccessLevel::get('LEVEL_COLLABORATOR')),
            ],
            $org->members->toArray(),
            'Members collection should equal, without duplicates'
        );

        self::assertEquals(new OrganizationMember($user1, $org, AccessLevel::get('LEVEL_MANAGER')), $org->getMember($user1));
    }

    /** @test */
    public function allows_removing_members(): void
    {
        $user1 = UserRepositoryMock::createUser(email: 'jant@example.com', id: '5c9bbbab-4743-48e6-aadb-e0827be9476a');
        $user2 = UserRepositoryMock::createUser(email: 'brooklynn@example.com', id: 'da02351c-e519-4306-bade-72f4f22f6f8f');

        $org = new Organization(OrganizationId::create(), 'Test Organization Inc.');
        $org->addMember($user1);
        $org->addMember($user2, AccessLevel::get('LEVEL_COLLABORATOR'));
        $org->removeMember($user2);

        self::assertEquals(
            [
                new OrganizationMember($user1, $org, AccessLevel::get('LEVEL_MANAGER')),
            ],
            $org->members->toArray(),
            'Members collection should equal'
        );
    }

    /** @test */
    public function allows_changing_member_access_level(): void
    {
        $user1 = UserRepositoryMock::createUser(email: 'jant@example.com', id: '5c9bbbab-4743-48e6-aadb-e0827be9476a');
        $user2 = UserRepositoryMock::createUser(email: 'brooklynn@example.com', id: 'da02351c-e519-4306-bade-72f4f22f6f8f');

        $org = new Organization(OrganizationId::create(), 'Test Organization Inc.');
        $org->addMember($user1);
        $org->addMember($user2, AccessLevel::get('LEVEL_COLLABORATOR'));
        $org->addMember($user2);

        self::assertEquals(
            [
                new OrganizationMember($user1, $org, AccessLevel::get('LEVEL_MANAGER')),
                new OrganizationMember($user2, $org, AccessLevel::get('LEVEL_MANAGER')),
            ],
            $org->members->toArray(),
            'Members collection should equal'
        );
    }

    /** @test */
    public function it_throws_when_getting_a_non_member_user(): void
    {
        $user1 = UserRepositoryMock::createUser(email: 'jant@example.com', id: '5c9bbbab-4743-48e6-aadb-e0827be9476a');
        $user2 = UserRepositoryMock::createUser(email: 'brooklynn@example.com', id: 'da02351c-e519-4306-bade-72f4f22f6f8f');

        $org = new Organization(OrganizationId::create(), 'Test Organization Inc.');
        $org->addMember($user1);

        $this->expectExceptionObject(OrganizationMemberNotFound::with($org->id, $user2->id));

        $org->getMember($user2);
    }
}
