<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Doctrine\Repository;

use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Organization\Organization;
use ParkManager\Domain\Organization\OrganizationId;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Infrastructure\Doctrine\Repository\OrganizationOrmRepository;
use ParkManager\Tests\Infrastructure\Doctrine\EntityRepositoryTestCase;

/**
 * @internal
 *
 * @group functional
 */
final class OrganizationOrmRepositoryTest extends EntityRepositoryTestCase
{
    private OrganizationOrmRepository $repository;

    private User $user;
    private User $user2;

    private Organization $organization;
    private Organization $organization2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::register(
            UserId::fromString('b848e67b-b091-4d46-94e1-e369d9530788'),
            new EmailAddress('org-user-test@example.com'),
            'Mocktor Who',
            'nope'
        );

        $this->user2 = User::register(
            UserId::fromString('649cd487-b6bc-4ed7-a4ad-0f894cdcfeb6'),
            new EmailAddress('org2-user-test@example.com'),
            'Donna NoBell',
            'nope-to-the-nope'
        );

        $em = $this->getEntityManager();
        $em->persist($this->user);
        $em->persist($this->user2);

        $this->organization = new Organization(OrganizationId::fromString('4e067c53-52ca-4bc1-879e-0270a78da248'), 'BitPub');
        $this->organization->addMember($this->user2);

        $this->organization2 = new Organization(OrganizationId::fromString('761c332a-5b81-4699-afac-3e305b452d12'), 'ByteBucket');
        $this->organization2->addMember($this->user2);

        $this->repository = new OrganizationOrmRepository($em);
        $this->repository->save($this->organization);
        $this->repository->save($this->organization2);

        $em->flush();
    }

    /** @test */
    public function it_gets_accessible_by_user(): void
    {
        self::assertSame([], iterator_to_array($this->repository->allAccessibleBy(UserId::fromString('b848e67b-b091-4d46-94e1-e369d9530788'))));
        self::assertSame(
            [
                $this->repository->get(OrganizationId::fromString('4e067c53-52ca-4bc1-879e-0270a78da248')),
                $this->repository->get(OrganizationId::fromString('761c332a-5b81-4699-afac-3e305b452d12')),
            ],
            iterator_to_array($this->repository->allAccessibleBy(UserId::fromString('649cd487-b6bc-4ed7-a4ad-0f894cdcfeb6')))
        );
    }
}
