<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Tests\UseCase\Account;

use ParkManager\Bundle\CoreBundle\Model\OwnerId;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccount;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountId;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountRepository;
use ParkManager\Bundle\WebhostingBundle\Model\DomainName;
use ParkManager\Bundle\WebhostingBundle\Model\DomainName\Exception\DomainNameAlreadyInUse;
use ParkManager\Bundle\WebhostingBundle\Model\DomainName\WebhostingDomainName;
use ParkManager\Bundle\WebhostingBundle\Model\DomainName\WebhostingDomainNameRepository;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraints;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlan;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlanId;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlanRepository;
use ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanConstraint\MonthlyTrafficQuota;
use ParkManager\Bundle\WebhostingBundle\UseCase\Account\RegisterWebhostingAccount;
use ParkManager\Bundle\WebhostingBundle\UseCase\Account\RegisterWebhostingAccountHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @internal
 */
final class RegisterWebhostingAccountHandlerTest extends TestCase
{
    private const OWNER_ID1 = '3f8da982-a528-11e7-a2da-acbc32b58315';
    private const PLAN_ID1 = '2570c850-a5e0-11e7-868d-acbc32b58315';

    private const ACCOUNT_ID1 = '2d3fb900-a528-11e7-a027-acbc32b58315';
    private const ACCOUNT_ID2 = '696d345c-a5e1-11e7-9856-acbc32b58315';

    /** @test */
    public function it_handles_registration_of_account_with_plan(): void
    {
        $constraints = new Constraints(new MonthlyTrafficQuota(50));
        $domainName = new DomainName('example', '.com');
        $webhostingPlan = new WebhostingPlan(WebhostingPlanId::fromString(self::PLAN_ID1), $constraints);
        $planRepository = $this->createPlanRepository($webhostingPlan);
        $accountRepository = $this->createAccountRepositoryThatSaves($constraints, $webhostingPlan);
        $domainNameRepository = $this->createDomainNameRepositoryThatSaves($domainName, self::ACCOUNT_ID1);
        $handler = new RegisterWebhostingAccountHandler($accountRepository, $planRepository, $domainNameRepository);

        $handler(
            RegisterWebhostingAccount::withPlan(
                self::ACCOUNT_ID1,
                $domainName,
                self::OWNER_ID1,
                self::PLAN_ID1
            )
        );
    }

    /** @test */
    public function it_handles_registration_of_account_with_custom_constraints(): void
    {
        $constraints = new Constraints(new MonthlyTrafficQuota(50));
        $domainName = new DomainName('example', '.com');
        $planRepository = $this->createNullPlanRepository();
        $accountRepository = $this->createAccountRepositoryThatSaves($constraints);
        $domainNameRepository = $this->createDomainNameRepositoryThatSaves($domainName, self::ACCOUNT_ID1);
        $handler = new RegisterWebhostingAccountHandler($accountRepository, $planRepository, $domainNameRepository);

        $handler(
            RegisterWebhostingAccount::withCustomConstraints(
                self::ACCOUNT_ID1,
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
        $accountId2 = WebhostingAccountId::fromString(self::ACCOUNT_ID2);
        $planRepository = $this->createNullPlanRepository();
        $accountRepository = $this->createAccountRepositoryWithoutSave();
        $domainNameRepository = $this->createDomainNameRepositoryWithExistingRegistration($domainName, $accountId2);
        $handler = new RegisterWebhostingAccountHandler($accountRepository, $planRepository, $domainNameRepository);

        $this->expectException(DomainNameAlreadyInUse::class);
        $this->expectExceptionMessage(DomainNameAlreadyInUse::byAccountId($domainName, $accountId2)->getMessage());

        $handler(
            RegisterWebhostingAccount::withPlan(
                self::ACCOUNT_ID1,
                $domainName,
                self::OWNER_ID1,
                self::PLAN_ID1
            )
        );
    }

    private function createAccountRepositoryThatSaves(Constraints $constraints, ?WebhostingPlan $plan = null, string $id = self::ACCOUNT_ID1, string $owner = self::OWNER_ID1): WebhostingAccountRepository
    {
        $accountRepositoryProphecy = $this->prophesize(WebhostingAccountRepository::class);
        $accountRepositoryProphecy->save(
            Argument::that(
                static function (WebhostingAccount $account) use ($constraints, $id, $owner, $plan) {
                    self::assertEquals(WebhostingAccountId::fromString($id), $account->getId());
                    self::assertEquals(OwnerId::fromString($owner), $account->getOwner());
                    self::assertEquals($constraints, $account->getPlanConstraints());
                    self::assertEquals($plan, $account->getPlan());

                    return true;
                }
            )
        )->shouldBeCalled();

        return $accountRepositoryProphecy->reveal();
    }

    private function createAccountRepositoryWithoutSave(): WebhostingAccountRepository
    {
        $accountRepositoryProphecy = $this->prophesize(WebhostingAccountRepository::class);
        $accountRepositoryProphecy->save(Argument::any())->shouldNotBeCalled();

        return $accountRepositoryProphecy->reveal();
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

    private function createDomainNameRepositoryThatSaves(DomainName $expectedDomain, string $accountId): WebhostingDomainNameRepository
    {
        $domainNameRepositoryProphecy = $this->prophesize(WebhostingDomainNameRepository::class);
        $domainNameRepositoryProphecy->findByFullName($expectedDomain)->willReturn(null);
        $domainNameRepositoryProphecy->save(
            Argument::that(
                static function (WebhostingDomainName $domain) use ($expectedDomain, $accountId) {
                    self::assertEquals($expectedDomain, $domain->getDomainName());
                    self::assertEquals(WebhostingAccountId::fromString($accountId), $domain->getAccount()->getId());

                    return true;
                }
            )
        )->shouldBeCalled();

        return $domainNameRepositoryProphecy->reveal();
    }

    private function createDomainNameRepositoryWithExistingRegistration(DomainName $expectedDomain, WebhostingAccountId $existingAccountId): WebhostingDomainNameRepository
    {
        $existingAccount = $this->createMock(WebhostingAccount::class);
        $existingAccount
            ->expects(static::any())
            ->method('getId')
            ->willReturn($existingAccountId)
        ;

        $existingDomain = $this->createMock(WebhostingDomainName::class);
        $existingDomain
            ->expects(static::any())
            ->method('getAccount')
            ->willReturn($existingAccount)
        ;

        $domainNameRepositoryProphecy = $this->prophesize(WebhostingDomainNameRepository::class);
        $domainNameRepositoryProphecy->findByFullName($expectedDomain)->willReturn($existingDomain);
        $domainNameRepositoryProphecy->save(Argument::any())->shouldNotBeCalled();

        return $domainNameRepositoryProphecy->reveal();
    }
}
