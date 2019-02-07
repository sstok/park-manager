<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Tests\Infrastructure\Doctrine\DomainName;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Module\CoreModule\Domain\Shared\OwnerId;
use ParkManager\Module\CoreModule\Test\Infrastructure\Doctrine\EntityRepositoryTestCase;
use ParkManager\Module\WebhostingModule\Domain\Account\Exception\WebhostingAccountNotFound;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccount;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountId;
use ParkManager\Module\WebhostingModule\Domain\DomainName;
use ParkManager\Module\WebhostingModule\Domain\DomainName\Exception\CannotRemovePrimaryDomainName;
use ParkManager\Module\WebhostingModule\Domain\DomainName\Exception\WebhostingDomainNameNotFound;
use ParkManager\Module\WebhostingModule\Domain\DomainName\WebhostingDomainName;
use ParkManager\Module\WebhostingModule\Domain\DomainName\WebhostingDomainNameId;
use ParkManager\Module\WebhostingModule\Domain\Package\Capabilities;
use ParkManager\Module\WebhostingModule\Infrastructure\Doctrine\DomainName\WebhostingDomainNameOrmRepository;

/**
 * @internal
 *
 * @group functional
 */
final class WebhostingDomainNameOrmRepositoryTest extends EntityRepositoryTestCase
{
    private const OWNER_ID1 = '3f8da982-a528-11e7-a2da-acbc32b58315';

    private const ACCOUNT_ID1  = '2d3fb900-a528-11e7-a027-acbc32b58315';
    private const ACCOUNT_ID2  = '47f6db14-a69c-11e7-be13-acbc32b58316';
    private const ACCOUNT_NOOP = '30b26ae0-a6b5-11e7-b978-acbc32b58315';

    /** @var WebhostingDomainNameOrmRepository */
    private $repository;

    /** @var WebhostingAccount */
    private $account1;

    /** @var WebhostingAccount */
    private $account2;

    /** @var WebhostingDomainNameId */
    private $id1;

    /** @var WebhostingDomainNameId */
    private $id2;

    /** @var WebhostingDomainNameId */
    private $id3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account1 = WebhostingAccount::registerWithCustomCapabilities(
            WebhostingAccountId::fromString(self::ACCOUNT_ID1),
            OwnerId::fromString(self::OWNER_ID1),
            new Capabilities()
        );

        $this->account2 = WebhostingAccount::registerWithCustomCapabilities(
            WebhostingAccountId::fromString(self::ACCOUNT_ID2),
            OwnerId::fromString(self::OWNER_ID1),
            new Capabilities()
        );

        $em = $this->getEntityManager();
        $em->transactional(function (EntityManagerInterface $em) {
            $em->persist($this->account1);
            $em->persist($this->account2);
        });

        $webhostingDomainName1 = WebhostingDomainName::registerPrimary($this->account1, new DomainName('example', 'com'));
        $this->id1             = $webhostingDomainName1->id();

        $webhostingDomainName2 = WebhostingDomainName::registerPrimary($this->account2, new DomainName('example', 'net'));
        $this->id2             = $webhostingDomainName2->id();

        $webhostingDomainName3 = WebhostingDomainName::registerSecondary($this->account2, new DomainName('example', 'co.uk'));
        $this->id3             = $webhostingDomainName3->id();

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

        self::assertTrue($webhostingDomainName->id()->equals($this->id1), 'ID should equal');
        self::assertEquals($this->account1, $webhostingDomainName->account());
        self::assertEquals(new DomainName('example', 'com'), $webhostingDomainName->domainName());
        self::assertTrue($webhostingDomainName->isPrimary());

        $webhostingDomainName = $this->repository->get($this->id2);

        self::assertTrue($webhostingDomainName->id()->equals($this->id2), 'ID should equal');
        self::assertEquals($this->account2, $webhostingDomainName->account());
        self::assertEquals(new DomainName('example', 'net'), $webhostingDomainName->domainName());
        self::assertTrue($webhostingDomainName->isPrimary());

        $webhostingDomainName = $this->repository->get($this->id3);

        self::assertTrue($webhostingDomainName->id()->equals($this->id3), 'ID should equal');
        self::assertEquals($this->account2, $webhostingDomainName->account());
        self::assertEquals(new DomainName('example', 'co.uk'), $webhostingDomainName->domainName());
        self::assertFalse($webhostingDomainName->isPrimary());
    }

    /** @test */
    public function it_gets_primary_of_account(): void
    {
        self::assertTrue($this->repository->getPrimaryOf($this->account1->id())->id()->equals($this->id1), 'ID should equal');
        self::assertTrue($this->repository->getPrimaryOf($this->account2->id())->id()->equals($this->id2), 'ID should equal');

        $this->expectException(WebhostingAccountNotFound::class);
        $this->expectExceptionMessage(
            WebhostingAccountNotFound::withId($id = WebhostingAccountId::fromString(self::ACCOUNT_NOOP))->getMessage()
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

        self::assertNotNull($domainName1);
        self::assertNotNull($domainName2);
        self::assertNull($domainName4);

        self::assertTrue($domainName1->id()->equals($this->id1), 'ID should equal');
        self::assertTrue($domainName2->id()->equals($this->id2), 'ID should equal');
        self::assertTrue($domainName3->id()->equals($this->id3), 'ID should equal');
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
            CannotRemovePrimaryDomainName::of($this->id1, $webhostingDomainName->account()->id())->getMessage()
        );

        $this->repository->remove($webhostingDomainName);
    }

    /** @test */
    public function it_marks_previous_primary_as_secondary(): void
    {
        $primaryDomainName   = $this->repository->get($this->id2);
        $secondaryDomainName = $this->repository->get($this->id3);

        $secondaryDomainName->markPrimary();
        $this->repository->save($secondaryDomainName);

        self::assertTrue($secondaryDomainName->isPrimary());
        self::assertFalse($primaryDomainName->isPrimary());
    }
}
