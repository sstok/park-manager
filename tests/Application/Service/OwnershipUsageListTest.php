<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Service;

use ParkManager\Application\Service\OwnershipUsageList;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\Owner;
use ParkManager\Domain\ResultSet;
use ParkManager\Tests\Mock\Domain\DomainName\DomainNameRepositoryMock;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class OwnershipUsageListTest extends TestCase
{
    /** @test */
    public function it_returns_whether_owner_is_assigned(): void
    {
        $owner1 = Owner::byUser(UserRepositoryMock::createUser());
        $owner2 = Owner::byUser(UserRepositoryMock::createUser(id: 'a9460c10-f178-40ca-a8ed-c7db84ebf083'));
        $owner3 = Owner::byUser(UserRepositoryMock::createUser(id: '183493f5-f7d1-4992-a7ac-13e7119e371c'));
        $owner4 = Owner::byUser(UserRepositoryMock::createUser(id: 'bfd9dc3b-4816-4869-bc52-2de6f3d345f2'));

        $repository = new SpaceRepositoryMock([
            SpaceRepositoryMock::createSpace(owner: $owner1),
            SpaceRepositoryMock::createSpace(owner: $owner3),
        ]);

        $repository2 = new DomainNameRepositoryMock([
            DomainName::register(DomainNameId::fromString('9f2e0b4f-274c-4571-a50a-685f7894fdec'), new DomainNamePair('example', 'com'), $owner2),
        ]);

        $list = new OwnershipUsageList(['space' => $repository, 'domainName' => $repository2]);

        self::assertTrue($list->isAnyAssignedTo($owner1->id));
        self::assertTrue($list->isAnyAssignedTo($owner2->id));
        self::assertFalse($list->isAnyAssignedTo($owner4->id));
    }

    /** @test */
    public function it_returns_all_entities_the_owner_is_assigned_to(): void
    {
        $owner1 = Owner::byUser(UserRepositoryMock::createUser());
        $owner2 = Owner::byUser(UserRepositoryMock::createUser(id: '183493f5-f7d1-4992-a7ac-13e7119e371c'));
        $owner3 = Owner::byUser(UserRepositoryMock::createUser(id: 'bfd9dc3b-4816-4869-bc52-2de6f3d345f2'));

        $repository = new SpaceRepositoryMock([
            $space1 = SpaceRepositoryMock::createSpace(owner: $owner1),
            $space2 = SpaceRepositoryMock::createSpace(owner: $owner2),
        ]);

        $repository2 = new DomainNameRepositoryMock([
            $domainName1 = DomainName::register(DomainNameId::fromString('9f2e0b4f-274c-4571-a50a-685f7894fdec'), new DomainNamePair('example', 'com'), $owner1),
            $domainName2 = DomainName::register(DomainNameId::fromString('849a5b32-bb7a-4c35-a899-d1b5995856ee'), new DomainNamePair('example', 'net'), $owner1),
        ]);

        $list = new OwnershipUsageList(['space' => $repository, 'domainName' => $repository2]);

        self::assertEquals([$space1, $domainName1, $domainName2], self::getEntities($list->getAllEntities($owner1->id)));
        self::assertEquals([$space2], self::getEntities($list->getAllEntities($owner2->id)));
        self::assertEquals([], self::getEntities($list->getAllEntities($owner3->id)));

        self::assertEquals(['space' => [$space1], 'domainName' => [$domainName1, $domainName2]], self::getEntities($list->getByProvider($owner1->id)));
        self::assertEquals(['space' => [$space2], 'domainName' => []], self::getEntities($list->getByProvider($owner2->id)));
        self::assertEquals(['space' => [], 'domainName' => []], self::getEntities($list->getByProvider($owner3->id)));
    }

    private static function getEntities(ResultSet | array $resultSet): array
    {
        if (\is_array($resultSet)) {
            $entities = [];

            foreach ($resultSet as $name => $set) {
                $entities[$name] = self::getEntities($set);
            }

            return $entities;
        }

        return \iterator_to_array($resultSet->getIterator());
    }
}
