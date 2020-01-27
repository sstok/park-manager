<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Domain\OwnerId;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceNotFound;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceId;
use ParkManager\Domain\Webhosting\DomainName;
use ParkManager\Domain\Webhosting\DomainName\Exception\CannotRemovePrimaryDomainName;
use ParkManager\Domain\Webhosting\DomainName\Exception\WebhostingDomainNameNotFound;
use ParkManager\Domain\Webhosting\DomainName\WebhostingDomainName;
use ParkManager\Domain\Webhosting\DomainName\WebhostingDomainNameId;
use ParkManager\Domain\Webhosting\Plan\Constraints;
use ParkManager\Infrastructure\Doctrine\Repository\WebhostingDomainNameOrmRepository;
use ParkManager\Tests\Infrastructure\Doctrine\EntityRepositoryTestCase;

/**
 * @internal
 *
 * @group functional
 */
final class WebhostingDomainNameOrmRepositoryTest extends EntityRepositoryTestCase
{
    private const OWNER_ID1 = '3f8da982-a528-11e7-a2da-acbc32b58315';

    private const SPACE_ID1 = '2d3fb900-a528-11e7-a027-acbc32b58315';
    private const SPACE_ID2 = '47f6db14-a69c-11e7-be13-acbc32b58316';
    private const SPACE_NOOP = '30b26ae0-a6b5-11e7-b978-acbc32b58315';

    /** @var WebhostingDomainNameOrmRepository */
    private $repository;

    /** @var Space */
    private $space1;

    /** @var Space */
    private $space2;

    /** @var WebhostingDomainNameId */
    private $id1;

    /** @var WebhostingDomainNameId */
    private $id2;

    /** @var WebhostingDomainNameId */
    private $id3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->space1 = Space::registerWithCustomConstraints(
            WebhostingSpaceId::fromString(self::SPACE_ID1),
            OwnerId::fromString(self::OWNER_ID1),
            new Constraints()
        );

        $this->space2 = Space::registerWithCustomConstraints(
            WebhostingSpaceId::fromString(self::SPACE_ID2),
            OwnerId::fromString(self::OWNER_ID1),
            new Constraints()
        );

        $em = $this->getEntityManager();
        $em->transactional(function (EntityManagerInterface $em): void {
            $em->persist($this->space1);
            $em->persist($this->space2);
        });

        $webhostingDomainName1 = WebhostingDomainName::registerPrimary($this->space1, new DomainName('example', 'com'));
        $this->id1 = $webhostingDomainName1->getId();

        $webhostingDomainName2 = WebhostingDomainName::registerPrimary($this->space2, new DomainName('example', 'net'));
        $this->id2 = $webhostingDomainName2->getId();

        $webhostingDomainName3 = WebhostingDomainName::registerSecondary($this->space2, new DomainName('example', 'co.uk'));
        $this->id3 = $webhostingDomainName3->getId();

        $this->repository = new WebhostingDomainNameOrmRepository($em);
        $this->repository->save($webhostingDomainName1);
        $this->repository->save($webhostingDomainName2);
        $this->repository->save($webhostingDomainName3);

        // Must be done explicit, normally handled by a transaction script.
        $em->flush();
    }

    /** @test */
    public function it_gets_existing_domain_name(): void
    {
        $webhostingDomainName = $this->repository->get($this->id1);

        static::assertTrue($webhostingDomainName->getId()->equals($this->id1), 'ID should equal');
        static::assertEquals($this->space1, $webhostingDomainName->getSpace());
        static::assertEquals(new DomainName('example', 'com'), $webhostingDomainName->getDomainName());
        static::assertTrue($webhostingDomainName->isPrimary());

        $webhostingDomainName = $this->repository->get($this->id2);

        static::assertTrue($webhostingDomainName->getId()->equals($this->id2), 'ID should equal');
        static::assertEquals($this->space2, $webhostingDomainName->getSpace());
        static::assertEquals(new DomainName('example', 'net'), $webhostingDomainName->getDomainName());
        static::assertTrue($webhostingDomainName->isPrimary());

        $webhostingDomainName = $this->repository->get($this->id3);

        static::assertTrue($webhostingDomainName->getId()->equals($this->id3), 'ID should equal');
        static::assertEquals($this->space2, $webhostingDomainName->getSpace());
        static::assertEquals(new DomainName('example', 'co.uk'), $webhostingDomainName->getDomainName());
        static::assertFalse($webhostingDomainName->isPrimary());
    }

    /** @test */
    public function it_gets_primary_of_space(): void
    {
        static::assertTrue($this->repository->getPrimaryOf($this->space1->getId())->getId()->equals($this->id1), 'ID should equal');
        static::assertTrue($this->repository->getPrimaryOf($this->space2->getId())->getId()->equals($this->id2), 'ID should equal');

        $this->expectException(WebhostingSpaceNotFound::class);
        $this->expectExceptionMessage(
            WebhostingSpaceNotFound::withId($id = WebhostingSpaceId::fromString(self::SPACE_NOOP))->getMessage()
        );

        $this->repository->getPrimaryOf($id);
    }

    /** @test */
    public function it_gets_by_name(): void
    {
        $domainName1 = $this->repository->findByFullName(new DomainName('example', 'com'));
        $domainName2 = $this->repository->findByFullName(new DomainName('example', 'net'));
        $domainName3 = $this->repository->findByFullName(new DomainName('example', 'co.uk'));
        $domainName4 = $this->repository->findByFullName(new DomainName('example', 'noop'));

        static::assertNotNull($domainName1);
        static::assertNotNull($domainName2);
        static::assertNull($domainName4);

        static::assertTrue($domainName1->getId()->equals($this->id1), 'ID should equal');
        static::assertTrue($domainName2->getId()->equals($this->id2), 'ID should equal');
        static::assertTrue($domainName3->getId()->equals($this->id3), 'ID should equal');
    }

    /** @test */
    public function it_removes_an_secondary_domain_name(): void
    {
        $webhostingDomainName = $this->repository->get($this->id3);

        $this->repository->remove($webhostingDomainName);
        $this->getEntityManager()->flush();

        $this->expectException(WebhostingDomainNameNotFound::class);
        $this->expectExceptionMessage(WebhostingDomainNameNotFound::withId($this->id3)->getMessage());

        $this->repository->get($this->id3);
    }

    /** @test */
    public function it_cannot_remove_a_primary_domain_name(): void
    {
        $webhostingDomainName = $this->repository->get($this->id1);

        $this->expectException(CannotRemovePrimaryDomainName::class);
        $this->expectExceptionMessage(
            CannotRemovePrimaryDomainName::of($this->id1, $webhostingDomainName->getSpace()->getId())->getMessage()
        );

        $this->repository->remove($webhostingDomainName);
    }

    /** @test */
    public function it_marks_previous_primary_as_secondary(): void
    {
        $primaryDomainName = $this->repository->get($this->id2);
        $secondaryDomainName = $this->repository->get($this->id3);

        $secondaryDomainName->markPrimary();
        $this->repository->save($secondaryDomainName);

        static::assertTrue($secondaryDomainName->isPrimary());
        static::assertFalse($primaryDomainName->isPrimary());
    }
}
