<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Doctrine\Repository;

use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use Lifthill\Component\Common\Domain\Model\EmailAddress;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\Owner;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Infrastructure\Doctrine\Repository\DomainNameOrmRepository;
use ParkManager\Tests\Infrastructure\Doctrine\EntityRepositoryTestCase;

/**
 * @internal
 *
 * @group functional
 */
final class OrmQueryBuilderResultSetTest extends EntityRepositoryTestCase
{
    private const OWNER_ID1 = '3f8da982-a528-11e7-a2da-acbc32b58315';
    private const OWNER_ID2 = '2794711b-00ab-403c-9331-8926353a4663';
    private const ID1 = '2d3fb900-a528-11e7-a027-acbc32b58315';
    private const ID2 = '47f6db14-a69c-11e7-be13-acbc32b58315';
    private const ID3 = '24802a63-2115-41e9-b7c8-0a1400583665';
    private const ID4 = '2b648417-aabe-4b05-84f1-f19231f0d5a6';

    /** @test */
    public function it_limits_ids(): void
    {
        $user = User::register(UserId::fromString(self::OWNER_ID1), new EmailAddress('John@mustash.com'), 'John', 'ashTong@8r949029');
        $user2 = User::register(UserId::fromString(self::OWNER_ID2), new EmailAddress('John2@mustash.com'), 'Johny2', 'ashTong@8r949029');

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->persist($user2);
        $em->persist($owner = Owner::byUser($user));
        $em->persist($owner2 = Owner::byUser($user2));

        $repository = new DomainNameOrmRepository($this->getEntityManager());
        $repository->save(DomainName::register(DomainNameId::fromString(self::ID1), new DomainNamePair('example', 'com'), $owner));
        $repository->save(DomainName::register(DomainNameId::fromString(self::ID2), new DomainNamePair('example', 'net'), $owner2));
        $repository->save(DomainName::register(DomainNameId::fromString(self::ID3), new DomainNamePair('example', 'nl'), $owner2));
        $repository->save(DomainName::register(DomainNameId::fromString(self::ID4), new DomainNamePair('example', 'io'), $owner2));

        $em = $this->getEntityManager();
        $em->flush();
        $em->clear();

        $this->assertIdsEquals([self::ID2, self::ID3, self::ID4], $repository->allFromOwner($owner2->id));
        $this->assertIdsEquals([self::ID2, self::ID4], $repository->allFromOwner($owner2->id)->limitToIds([self::ID2, self::ID4]));
        $this->assertIdsEquals([], $repository->allFromOwner($owner2->id)->limitToIds([self::ID1])); // can't expend the subselection
    }
}
