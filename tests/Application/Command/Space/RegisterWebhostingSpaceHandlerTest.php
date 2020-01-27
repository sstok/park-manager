<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\Space;

use ParkManager\Application\Command\Webhosting\Space\RegisterWebhostingSpace;
use ParkManager\Application\Command\Webhosting\Space\RegisterWebhostingSpaceHandler;
use ParkManager\Tests\Infrastructure\Webhosting\Fixtures\MonthlyTrafficQuota;
use ParkManager\Domain\OwnerId;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceId;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceRepository;
use ParkManager\Domain\Webhosting\DomainName;
use ParkManager\Domain\Webhosting\DomainName\Exception\DomainNameAlreadyInUse;
use ParkManager\Domain\Webhosting\DomainName\WebhostingDomainName;
use ParkManager\Domain\Webhosting\DomainName\WebhostingDomainNameRepository;
use ParkManager\Domain\Webhosting\Plan\Constraints;
use ParkManager\Domain\Webhosting\Plan\WebhostingPlan;
use ParkManager\Domain\Webhosting\Plan\WebhostingPlanId;
use ParkManager\Domain\Webhosting\Plan\WebhostingPlanRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @internal
 */
final class RegisterWebhostingSpaceHandlerTest extends TestCase
{
    private const OWNER_ID1 = '3f8da982-a528-11e7-a2da-acbc32b58315';
    private const PLAN_ID1 = '2570c850-a5e0-11e7-868d-acbc32b58315';

    private const SPACE_ID1 = '2d3fb900-a528-11e7-a027-acbc32b58315';
    private const SPACE_ID2 = '696d345c-a5e1-11e7-9856-acbc32b58315';

    /** @test */
    public function it_handles_registration_of_space_with_plan(): void
    {
        $constraints = new Constraints(new MonthlyTrafficQuota(50));
        $domainName = new DomainName('example', '.com');
        $webhostingPlan = new WebhostingPlan(WebhostingPlanId::fromString(self::PLAN_ID1), $constraints);
        $planRepository = $this->createPlanRepository($webhostingPlan);
        $spaceRepository = $this->createSpaceRepositoryThatSaves($constraints, $webhostingPlan);
        $domainNameRepository = $this->createDomainNameRepositoryThatSaves($domainName, self::SPACE_ID1);
        $handler = new RegisterWebhostingSpaceHandler($spaceRepository, $planRepository, $domainNameRepository);

        $handler(
            RegisterWebhostingSpace::withPlan(
                self::SPACE_ID1,
                $domainName,
                self::OWNER_ID1,
                self::PLAN_ID1
            )
        );
    }

    /** @test */
    public function it_handles_registration_of_space_with_custom_constraints(): void
    {
        $constraints = new Constraints(new MonthlyTrafficQuota(50));
        $domainName = new DomainName('example', '.com');
        $planRepository = $this->createNullPlanRepository();
        $spaceRepository = $this->createSpaceRepositoryThatSaves($constraints);
        $domainNameRepository = $this->createDomainNameRepositoryThatSaves($domainName, self::SPACE_ID1);
        $handler = new RegisterWebhostingSpaceHandler($spaceRepository, $planRepository, $domainNameRepository);

        $handler(
            RegisterWebhostingSpace::withCustomConstraints(
                self::SPACE_ID1,
                new DomainName('example', '.com'),
                self::OWNER_ID1,
                $constraints
            )
        );
    }

    /** @test */
    public function it_checks_domain_is_not_already_registered(): void
    {
        $domainName = new DomainName('example', '.com');
        $spaceId2 = WebhostingSpaceId::fromString(self::SPACE_ID2);
        $planRepository = $this->createNullPlanRepository();
        $spaceRepository = $this->createSpaceRepositoryWithoutSave();
        $domainNameRepository = $this->createDomainNameRepositoryWithExistingRegistration($domainName, $spaceId2);
        $handler = new RegisterWebhostingSpaceHandler($spaceRepository, $planRepository, $domainNameRepository);

        $this->expectException(DomainNameAlreadyInUse::class);
        $this->expectExceptionMessage(DomainNameAlreadyInUse::bySpaceId($domainName, $spaceId2)->getMessage());

        $handler(
            RegisterWebhostingSpace::withPlan(
                self::SPACE_ID1,
                $domainName,
                self::OWNER_ID1,
                self::PLAN_ID1
            )
        );
    }

    private function createSpaceRepositoryThatSaves(Constraints $constraints, ?WebhostingPlan $plan = null, string $id = self::SPACE_ID1, string $owner = self::OWNER_ID1): WebhostingSpaceRepository
    {
        $spaceRepositoryProphecy = $this->prophesize(WebhostingSpaceRepository::class);
        $spaceRepositoryProphecy->save(
            Argument::that(
                static function (Space $space) use ($constraints, $id, $owner, $plan) {
                    self::assertEquals(WebhostingSpaceId::fromString($id), $space->getId());
                    self::assertEquals(OwnerId::fromString($owner), $space->getOwner());
                    self::assertEquals($constraints, $space->getPlanConstraints());
                    self::assertEquals($plan, $space->getPlan());

                    return true;
                }
            )
        )->shouldBeCalled();

        return $spaceRepositoryProphecy->reveal();
    }

    private function createSpaceRepositoryWithoutSave(): WebhostingSpaceRepository
    {
        $spaceRepositoryProphecy = $this->prophesize(WebhostingSpaceRepository::class);
        $spaceRepositoryProphecy->save(Argument::any())->shouldNotBeCalled();

        return $spaceRepositoryProphecy->reveal();
    }

    private function createNullPlanRepository(): WebhostingPlanRepository
    {
        return $this->createMock(WebhostingPlanRepository::class);
    }

    private function createPlanRepository(WebhostingPlan $plan): WebhostingPlanRepository
    {
        $planRepositoryProphecy = $this->prophesize(WebhostingPlanRepository::class);
        $planRepositoryProphecy->get($plan->getId())->willReturn($plan);

        return $planRepositoryProphecy->reveal();
    }

    private function createDomainNameRepositoryThatSaves(DomainName $expectedDomain, string $spaceId): WebhostingDomainNameRepository
    {
        $domainNameRepositoryProphecy = $this->prophesize(WebhostingDomainNameRepository::class);
        $domainNameRepositoryProphecy->findByFullName($expectedDomain)->willReturn(null);
        $domainNameRepositoryProphecy->save(
            Argument::that(
                static function (WebhostingDomainName $domain) use ($expectedDomain, $spaceId) {
                    self::assertEquals($expectedDomain, $domain->getDomainName());
                    self::assertEquals(WebhostingSpaceId::fromString($spaceId), $domain->getSpace()->getId());

                    return true;
                }
            )
        )->shouldBeCalled();

        return $domainNameRepositoryProphecy->reveal();
    }

    private function createDomainNameRepositoryWithExistingRegistration(DomainName $expectedDomain, WebhostingSpaceId $existingSpaceId): WebhostingDomainNameRepository
    {
        $existingSpace = $this->createMock(Space::class);
        $existingSpace
            ->expects(static::any())
            ->method('getId')
            ->willReturn($existingSpaceId);

        $existingDomain = $this->createMock(WebhostingDomainName::class);
        $existingDomain
            ->expects(static::any())
            ->method('getSpace')
            ->willReturn($existingSpace);

        $domainNameRepositoryProphecy = $this->prophesize(WebhostingDomainNameRepository::class);
        $domainNameRepositoryProphecy->findByFullName($expectedDomain)->willReturn($existingDomain);
        $domainNameRepositoryProphecy->save(Argument::any())->shouldNotBeCalled();

        return $domainNameRepositoryProphecy->reveal();
    }
}
