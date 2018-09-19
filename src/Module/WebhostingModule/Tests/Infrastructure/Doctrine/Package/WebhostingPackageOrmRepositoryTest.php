<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Tests\Infrastructure\Doctrine\Package;

use ParkManager\Module\CoreModule\Test\Domain\EventSourcedRepositoryTestHelper;
use ParkManager\Module\CoreModule\Test\Infrastructure\Doctrine\EntityRepositoryTestCase;
use ParkManager\Module\WebhostingModule\Domain\Package\Capabilities;
use ParkManager\Module\WebhostingModule\Domain\Package\Exception\WebhostingPackageNotFound;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackage;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackageId;
use ParkManager\Module\WebhostingModule\Infrastructure\Doctrine\Package\WebhostingPackageOrmRepository;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Domain\PackageCapability\MonthlyTrafficQuota;

/**
 * @internal
 * @group functional
 */
final class WebhostingPackageOrmRepositoryTest extends EntityRepositoryTestCase
{
    use EventSourcedRepositoryTestHelper;

    private const PACKAGE_ID1 = '2570c850-a5e0-11e7-868d-acbc32b58315';
    private const PACKAGE_ID2 = '3bd0fa08-a756-11e7-bdf0-acbc32b58315';

    /** @test */
    public function it_gets_existing_packages()
    {
        $repository = $this->createRepository(2);
        $this->setUpPackage1($repository);
        $this->setUpPackage2($repository);

        $id  = WebhostingPackageId::fromString(self::PACKAGE_ID1);
        $id2 = WebhostingPackageId::fromString(self::PACKAGE_ID2);

        $package  = $repository->get($id);
        $package2 = $repository->get($id2);

        self::assertEquals($id, $package->id());
        self::assertEquals(['title' => 'Supper Gold XL'], $package->metadata());
        self::assertEquals(new Capabilities(new MonthlyTrafficQuota(5)), $package->capabilities());

        self::assertEquals($id2, $package2->id());
        self::assertEquals([], $package2->metadata());
        self::assertEquals(new Capabilities(new MonthlyTrafficQuota(50)), $package2->capabilities());
    }

    /** @test */
    public function it_removes_an_existing_package()
    {
        $repository = $this->createRepository(2);
        $this->setUpPackage1($repository);
        $this->setUpPackage2($repository);

        $id      = WebhostingPackageId::fromString(self::PACKAGE_ID1);
        $id2     = WebhostingPackageId::fromString(self::PACKAGE_ID2);
        $package = $repository->get($id);

        $repository->remove($package);
        $this->assertInTransaction();

        $repository->get($id2);

        // Assert actually removed
        $this->expectException(WebhostingPackageNotFound::class);
        $this->expectExceptionMessage(WebhostingPackageNotFound::withId($id)->getMessage());
        $repository->get($id);
    }

    private function createRepository(int $expectedEventsCount): WebhostingPackageOrmRepository
    {
        return new WebhostingPackageOrmRepository(
            $this->getEntityManager(),
            $this->createEventsExpectingEventBus($expectedEventsCount)
        );
    }

    private function setUpPackage1(WebhostingPackageOrmRepository $repository)
    {
        $package = WebhostingPackage::create(
            WebhostingPackageId::fromString(self::PACKAGE_ID1),
            new Capabilities(new MonthlyTrafficQuota(5))
        );
        $package->withMetadata(['title' => 'Supper Gold XL']);

        $repository->save($package);
        $this->assertInTransaction();
    }

    private function setUpPackage2(WebhostingPackageOrmRepository $repository)
    {
        $repository->save(
            WebhostingPackage::create(
                WebhostingPackageId::fromString(self::PACKAGE_ID2),
                new Capabilities(new MonthlyTrafficQuota(50))
            )
        );
        $this->assertInTransaction();
    }
}
