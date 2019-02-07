<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Tests\Infrastructure\Doctrine\Account;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Module\CoreModule\Domain\Shared\OwnerId;
use ParkManager\Module\CoreModule\Test\Domain\EventSourcedRepositoryTestHelper;
use ParkManager\Module\CoreModule\Test\Infrastructure\Doctrine\EntityRepositoryTestCase;
use ParkManager\Module\WebhostingModule\Domain\Account\Exception\CannotRemoveActiveWebhostingAccount;
use ParkManager\Module\WebhostingModule\Domain\Account\Exception\WebhostingAccountNotFound;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccount;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountId;
use ParkManager\Module\WebhostingModule\Domain\Package\Capabilities;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackage;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackageId;
use ParkManager\Module\WebhostingModule\Infrastructure\Doctrine\Account\WebhostingAccountOrmRepository;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Domain\PackageCapability\MonthlyTrafficQuota;

/**
 * @internal
 *
 * @group functional
 */
final class WebhostingAccountOrmRepositoryTest extends EntityRepositoryTestCase
{
    use EventSourcedRepositoryTestHelper;

    private const OWNER_ID1   = '3f8da982-a528-11e7-a2da-acbc32b58315';
    private const PACKAGE_ID1 = '2570c850-a5e0-11e7-868d-acbc32b58315';

    private const ACCOUNT_ID1 = '2d3fb900-a528-11e7-a027-acbc32b58315';
    private const ACCOUNT_ID2 = '47f6db14-a69c-11e7-be13-acbc32b58315';

    /** @var Capabilities */
    private $packageCapabilities;

    /** @var WebhostingPackage */
    private $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->packageCapabilities = new Capabilities(new MonthlyTrafficQuota(50));
        $this->package             = WebhostingPackage::create(
            WebhostingPackageId::fromString(self::PACKAGE_ID1),
            $this->packageCapabilities
        );

        $em = $this->getEntityManager();
        $em->transactional(function (EntityManagerInterface $em) {
            $em->persist($this->package);
        });
    }

    /** @test */
    public function it_gets_existing_accounts(): void
    {
        $repository = $this->createRepository(2);
        $this->setUpAccount1($repository);
        $this->setUpAccount2($repository);

        $id       = WebhostingAccountId::fromString(self::ACCOUNT_ID1);
        $id2      = WebhostingAccountId::fromString(self::ACCOUNT_ID2);
        $account  = $repository->get($id);
        $account2 = $repository->get($id2);

        self::assertEquals($id, $account->id());
        self::assertEquals(OwnerId::fromString(self::OWNER_ID1), $account->owner());
        self::assertEquals(new Capabilities(), $account->capabilities());
        self::assertNull($account->package());

        self::assertEquals($id2, $account2->id());
        self::assertEquals(OwnerId::fromString(self::OWNER_ID1), $account2->owner());
        self::assertEquals($this->packageCapabilities, $account2->capabilities());
        self::assertEquals($this->package, $account2->package());
    }

    /** @test */
    public function it_removes_an_existing_model(): void
    {
        $repository = $this->createRepository(3);
        $this->setUpAccount1($repository);
        $this->setUpAccount2($repository);

        $id      = WebhostingAccountId::fromString(self::ACCOUNT_ID1);
        $account = $repository->get($id);

        // Mark for removal, then store this status.
        $account->markForRemoval();
        $repository->save($account);

        // Later another process will perform the removal operation
        $repository->remove($account);
        $this->assertInTransaction();

        // Assert actually removed
        $this->expectException(WebhostingAccountNotFound::class);
        $this->expectExceptionMessage(WebhostingAccountNotFound::withId($id)->getMessage());
        $repository->get($id);
    }

    /** @test */
    public function it_checks_account_is_marked_for_removal(): void
    {
        $repository = $this->createRepository(1);
        $this->setUpAccount1($repository);

        $id      = WebhostingAccountId::fromString(self::ACCOUNT_ID1);
        $account = $repository->get($id);

        $this->expectException(CannotRemoveActiveWebhostingAccount::class);
        $this->expectExceptionMessage(CannotRemoveActiveWebhostingAccount::withId($id)->getMessage());

        $repository->remove($account);
    }

    private function createRepository(int $expectedEventsCount): WebhostingAccountOrmRepository
    {
        return new WebhostingAccountOrmRepository(
            $this->getEntityManager(),
            $this->createEventsExpectingEventBus($expectedEventsCount)
        );
    }

    private function setUpAccount1(WebhostingAccountOrmRepository $repository): void
    {
        $repository->save(
            WebhostingAccount::registerWithCustomCapabilities(
                WebhostingAccountId::fromString(self::ACCOUNT_ID1),
                OwnerId::fromString(self::OWNER_ID1),
                new Capabilities()
            )
        );
        $this->assertInTransaction();
    }

    private function setUpAccount2(WebhostingAccountOrmRepository $repository): void
    {
        $repository->save(
            WebhostingAccount::register(
                WebhostingAccountId::fromString(self::ACCOUNT_ID2),
                OwnerId::fromString(self::OWNER_ID1),
                $this->package
            )
        );
        $this->assertInTransaction();
    }
}
