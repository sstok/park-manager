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

namespace ParkManager\Module\Webhosting\Tests\Service\Package;

use ParkManager\Component\Model\LogMessage\LogMessage;
use ParkManager\Component\Model\LogMessage\LogMessages;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccount;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccountId;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccountRepository;
use ParkManager\Module\Webhosting\Model\Package\Capabilities;
use ParkManager\Module\Webhosting\Model\Package\CapabilityGuard;
use ParkManager\Module\Webhosting\Service\Package\AccountCapabilitiesGuard;
use ParkManager\Module\Webhosting\Service\Package\CapabilitiesRegistry;
use ParkManager\Module\Webhosting\Tests\Fixtures\Capability\StorageSpaceQuota;
use ParkManager\Module\Webhosting\Tests\Fixtures\Model\Package\Capability\MonthlyTrafficQuota;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 */
final class AccountCapabilitiesGuardTest extends TestCase
{
    private const ACCOUNT_ID = '374dd50e-9b9f-11e7-9730-acbc32b58315';
    private const ACCOUNT_ID2 = '374dd50e-9b9f-11e7-9730-acbc32b58316';

    /**
     * @var AccountCapabilitiesGuard
     */
    private $capabilitiesGuard;

    protected function setUp(): void
    {
        $account1 = $this->createMock(WebhostingAccount::class);
        $account1->expects(self::atMost(1))
            ->method('capabilities')
            ->willReturn(new Capabilities($capability = new MonthlyTrafficQuota(50)));

        $account2 = $this->createMock(WebhostingAccount::class);
        $account2->expects(self::atMost(1))
            ->method('capabilities')
            ->willReturn(new Capabilities(new StorageSpaceQuota('1GB')));

        $repositoryProphecy = $this->prophesize(WebhostingAccountRepository::class);
        $repositoryProphecy->get(WebhostingAccountId::fromString(self::ACCOUNT_ID))->willReturn($account1);
        $repositoryProphecy->get(WebhostingAccountId::fromString(self::ACCOUNT_ID2))->willReturn($account2);
        $accountRepository = $repositoryProphecy->reveal();

        $capabilitiesRegistry = new CapabilitiesRegistry(
            [
                MonthlyTrafficQuota::class => ['guard' => MonthlyTrafficQuota::class],
                StorageSpaceQuota::class => ['guard' => null],
            ],
            [
                MonthlyTrafficQuota::id() => MonthlyTrafficQuota::class,
                StorageSpaceQuota::id() => StorageSpaceQuota::class,
            ],
            new ServiceLocator(
                [
                    MonthlyTrafficQuota::class => function () use ($capability, $account1, $account2) {
                        $capabilitiesGuard = $this->prophesize(CapabilityGuard::class);
                        $capabilitiesGuard->can($capability, $account2, Argument::any())->willReturn(true);
                        $capabilitiesGuard->can($capability, $account1, Argument::any())->will(function ($args) {
                            /** @var MonthlyTrafficQuota $args[0] */
                            /** @var LogMessages $args[3] */
                            $args[2]->add(LogMessage::error('It failed '.$args[0]->configuration()['limit']));

                            return false;
                        });

                        return $capabilitiesGuard->reveal();
                    },
                ]
            ),
            new ServiceLocator([])
        );

        $this->capabilitiesGuard = new AccountCapabilitiesGuard($accountRepository, $capabilitiesRegistry);
    }

    /** @test */
    public function it_works_when_none_of_provided_capabilities_is_present_on_account()
    {
        $messages = $this->capabilitiesGuard->allowedTo(
            WebhostingAccountId::fromString(self::ACCOUNT_ID2),
            MonthlyTrafficQuota::class
        );

        self::assertCount(0, $messages);
    }

    /** @test */
    public function it_it_asks_guard_for_provided_capabilities_is_present_on_account()
    {
        $messages = $this->capabilitiesGuard->allowedTo(
            WebhostingAccountId::fromString(self::ACCOUNT_ID),
            MonthlyTrafficQuota::class
        );

        self::assertCount(1, $messages);
        self::assertEquals(['error' => [LogMessage::error('It failed 50')]], $messages->all());
    }
}
