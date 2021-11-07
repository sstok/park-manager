<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Owner;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Infrastructure\Doctrine\Repository\DomainNameOrmRepository;
use ParkManager\Infrastructure\Doctrine\Repository\SpaceOrmRepository;
use ParkManager\Tests\Infrastructure\Doctrine\EntityRepositoryTestCase;

/**
 * @internal
 *
 * @group functional
 */
abstract class WebhostingRepositoryTestCase extends EntityRepositoryTestCase
{
    protected const SPACE_ID1 = '65f41c60-89b6-4e7d-870c-1dd6d61157aa';
    protected const SPACE_ID2 = '9f4233ea-23df-4ee5-af3b-997e582136ac';

    protected DomainNameOrmRepository $domainRepository;
    protected SpaceOrmRepository $spaceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $em = $this->getEntityManager();

        $this->domainRepository = new DomainNameOrmRepository($em);
        $this->spaceRepository = new SpaceOrmRepository($em);

        $em->wrapInTransaction(function ($em): void {
            $owner = $this->createOwner($em);

            // Note that we only care about the existence of multiple spaces, but not their owner.
            $this->spaceRepository->save($space1 = Space::registerWithCustomConstraints(SpaceId::fromString(self::SPACE_ID1), $owner, new Constraints()));
            $this->spaceRepository->save($space2 = Space::registerWithCustomConstraints(SpaceId::fromString(self::SPACE_ID2), $owner, new Constraints()));

            $this->domainRepository->save(DomainName::registerForSpace(DomainNameId::fromString(self::SPACE_ID1), $space1, new DomainNamePair('example', 'com')));
            $this->domainRepository->save(DomainName::registerForSpace(DomainNameId::fromString(self::SPACE_ID2), $space2, new DomainNamePair('example', 'net')));
        });
    }

    private function createOwner(EntityManagerInterface $em): Owner
    {
        $user = User::register(
            UserId::fromString('57824f85-d5db-4732-8333-cf51a0b268c2'),
            new EmailAddress('John2me@mustash.com'),
            'John the II',
            'ashTong@8r949029'
        );
        $owner = Owner::byUser($user);

        $em->persist($user);
        $em->persist($owner);

        return $owner;
    }
}
