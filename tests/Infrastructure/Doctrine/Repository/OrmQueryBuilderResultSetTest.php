<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Doctrine\Repository;

use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Infrastructure\Doctrine\Repository\WebhostingSpaceOrmRepository;
use ParkManager\Tests\Infrastructure\Doctrine\EntityRepositoryTestCase;

/**
 * @internal
 *
 * @group functional
 */
final class OrmQueryBuilderResultSetTest extends EntityRepositoryTestCase
{
    private const OWNER_ID1 = '3f8da982-a528-11e7-a2da-acbc32b58315';
    private const SPACE_ID1 = '2d3fb900-a528-11e7-a027-acbc32b58315';
    private const SPACE_ID2 = '47f6db14-a69c-11e7-be13-acbc32b58315';
    private const SPACE_ID3 = '24802a63-2115-41e9-b7c8-0a1400583665';
    private const SPACE_ID4 = '2b648417-aabe-4b05-84f1-f19231f0d5a6';

    /** @test */
    public function it_limits_ids(): void
    {
        $user = User::register(UserId::fromString(self::OWNER_ID1), new EmailAddress('John@mustash.com'), 'John', 'ashTong@8r949029');

        $em = $this->getEntityManager();
        $em->persist($user);

        $repository = new WebhostingSpaceOrmRepository($this->getEntityManager());
        $repository->save(Space::registerWithCustomConstraints(SpaceId::fromString(self::SPACE_ID1), $user, new Constraints()));
        $repository->save(Space::registerWithCustomConstraints(SpaceId::fromString(self::SPACE_ID2), null, new Constraints()));
        $repository->save(Space::registerWithCustomConstraints(SpaceId::fromString(self::SPACE_ID3), null, new Constraints()));
        $repository->save(Space::registerWithCustomConstraints(SpaceId::fromString(self::SPACE_ID4), null, new Constraints()));

        $em = $this->getEntityManager();
        $em->flush();
        $em->clear();

        $this->assertIdsEquals([self::SPACE_ID2, self::SPACE_ID3, self::SPACE_ID4], $repository->allFromOwner(null));
        $this->assertIdsEquals([self::SPACE_ID2, self::SPACE_ID4], $repository->allFromOwner(null)->limitToIds([self::SPACE_ID2, self::SPACE_ID4]));
        $this->assertIdsEquals([], $repository->allFromOwner(null)->limitToIds([self::SPACE_ID1])); // can't expend the subselection
    }
}
