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

namespace ParkManager\Module\WebhostingModule\Tests\Service\Package;

use ParkManager\Component\ApplicationFoundation\Message\ServiceMessage;
use ParkManager\Component\ApplicationFoundation\Message\ServiceMessages;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccount;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountId;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountRepository;
use ParkManager\Module\WebhostingModule\Domain\Package\Capabilities;
use ParkManager\Module\WebhostingModule\Domain\Package\Capability;
use ParkManager\Module\WebhostingModule\Infrastructure\Service\Package\AccountCapabilitiesRestrictionGuard;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Application\Ftp\RegisterFtpUser;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Application\Mailbox\CreateMailbox;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Application\Mailbox\RemoveMailbox;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Domain\PackageCapability\{
    FtpUserCount, MonthlyTrafficQuota, StorageSpaceQuota
};
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Infrastructure\PackageCapability\{
    AllowingWithWarningsGuard, DenyingGuard
};
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\PropertyAccess\PropertyAccessorBuilder;

/**
 * @internal
 */
final class AccountCapabilitiesGuardTest extends TestCase
{
    private const ACCOUNT_ID1 = '374dd50e-9b9f-11e7-9730-acbc32b58315';
    private const ACCOUNT_ID2 = '374dd50e-9b9f-11e7-9730-acbc32b58316';

    /**
     * @var AccountCapabilitiesRestrictionGuard
     */
    private $capabilitiesGuard;

    protected function setUp(): void
    {
        $account1 = $this->createAccountMock(self::ACCOUNT_ID1, new MonthlyTrafficQuota(50), new FtpUserCount(5));
        $account2 = $this->createAccountMock(self::ACCOUNT_ID2, new StorageSpaceQuota('1GB'));

        $repositoryProphecy = $this->prophesize(WebhostingAccountRepository::class);
        $repositoryProphecy->get(WebhostingAccountId::fromString(self::ACCOUNT_ID1))->willReturn($account1);
        $repositoryProphecy->get(WebhostingAccountId::fromString(self::ACCOUNT_ID2))->willReturn($account2);
        $accountRepository = $repositoryProphecy->reveal();

        $this->capabilitiesGuard = new AccountCapabilitiesRestrictionGuard(
            $accountRepository,
            new ServiceLocator(
                [
                    StorageSpaceQuota::class => function () {
                        return new DenyingGuard();
                    },
                    FtpUserCount::class => function () {
                        return new AllowingWithWarningsGuard();
                    },
                ]
            ),
            (new PropertyAccessorBuilder())->enableExceptionOnInvalidIndex()->getPropertyAccessor(),
            [
                CreateMailbox::class => [
                    'capability' => StorageSpaceQuota::class,
                    'mapping' => ['limit' => 'sizeInBytes'],
                ],
                RegisterFtpUser::class => [
                    'capability' => FtpUserCount::class,
                ],
            ]
        );
    }

    /** @test */
    public function it_decides_to_pass_when_capabilities_are_not_present_on_account()
    {
        $messages = new ServiceMessages();

        self::assertTrue($this->capabilitiesGuard->decide(new CreateMailbox(self::ACCOUNT_ID1, 5), $messages));
        self::assertTrue($this->capabilitiesGuard->decide(new RemoveMailbox(self::ACCOUNT_ID2), $messages));
        self::assertCount(0, $messages);
    }

    /** @test */
    public function it_decides_to_reject_when_capability_guard_rejects()
    {
        $messages = new ServiceMessages();
        self::assertFalse($this->capabilitiesGuard->decide(new CreateMailbox(self::ACCOUNT_ID2, 5), $messages));
        self::assertEquals(['error' => [ServiceMessage::error('It failed 5')]], $messages->all());
    }

    /** @test */
    public function it_decides_to_pass_when_capability_guard_approves()
    {
        $messages = new ServiceMessages();
        self::assertTrue($this->capabilitiesGuard->decide(new RegisterFtpUser(self::ACCOUNT_ID1), $messages));
        self::assertEquals(['warning' => [ServiceMessage::warning('Hold it there, you are about to get stuck NULL')]], $messages->all());
    }

    private function createAccountMock(string $id, Capability ...$capabilities): WebhostingAccount
    {
        $account = $this->createMock(WebhostingAccount::class);
        $account->expects(self::atMost(1))
            ->method('capabilities')
            ->willReturn(new Capabilities(...$capabilities));

        $account->expects(self::any())
            ->method('id')
            ->willReturn(WebhostingAccountId::fromString($id));

        return $account;
    }
}
