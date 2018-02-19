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
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccountId;
use ParkManager\Module\Webhosting\Model\Package\CapabilitiesGuard;
use ParkManager\Module\Webhosting\Service\Package\CommandToCapabilitiesGuard;
use ParkManager\Module\Webhosting\Tests\Fixtures\Capability\{MailboxCountCount, StorageSpaceQuota};
use ParkManager\Module\Webhosting\Tests\Fixtures\Model\Mailbox\CreateMailbox;
use ParkManager\Module\Webhosting\Tests\Fixtures\Model\Mailbox\RemoveMailbox;
use ParkManager\Module\Webhosting\Tests\Fixtures\Model\Package\Command\CreatePackage;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @internal
 */
final class CommandToCapabilitiesGuardTest extends TestCase
{
    private const ACCOUNT_ID = '2d3fb900-a528-11e7-a027-acbc32b58315';

    /** @test */
    public function it_ignores_unsupported_commands()
    {
        $commandGuard = new CommandToCapabilitiesGuard($this->createUnusedCapabilitiesGuard());

        self::assertEquals(new LogMessages(), $commandGuard->commandAllowedFor(
            new CreatePackage(),
            WebhostingAccountId::fromString(self::ACCOUNT_ID)
        ));
    }

    /** @test */
    public function it_maps_command_to_capabilities()
    {
        $logMessages = new LogMessages();
        $logMessages->add(LogMessage::error('Cannot let you do this John, your mailbox limit is reached.'));

        $commandGuard = new CommandToCapabilitiesGuard($this->createCapabilitiesGuard($logMessages, []));

        self::assertEquals($logMessages, $commandGuard->commandAllowedFor(
            new CreateMailbox(WebhostingAccountId::fromString(self::ACCOUNT_ID), 500),
            WebhostingAccountId::fromString(self::ACCOUNT_ID)
        ));

        self::assertEquals(new LogMessages(), $commandGuard->commandAllowedFor(
            new RemoveMailbox(WebhostingAccountId::fromString(self::ACCOUNT_ID)),
            WebhostingAccountId::fromString(self::ACCOUNT_ID)
        ));
    }

    /** @test */
    public function it_maps_command_to_capabilities_with_context()
    {
        $logMessages = new LogMessages();
        $logMessages->add(LogMessage::error('Cannot let you do this John, your mailbox limit is reached.'));

        $commandGuard = new CommandToCapabilitiesGuard(
            $this->createCapabilitiesGuard($logMessages, ['account' => self::ACCOUNT_ID]),
            function ($command, $account) {
                return ['account' => (string) $account];
            }
        );

        self::assertEquals($logMessages, $commandGuard->commandAllowedFor(
            new CreateMailbox(WebhostingAccountId::fromString(self::ACCOUNT_ID), 500),
            WebhostingAccountId::fromString(self::ACCOUNT_ID)
        ));

        self::assertEquals(new LogMessages(), $commandGuard->commandAllowedFor(
            new RemoveMailbox(WebhostingAccountId::fromString(self::ACCOUNT_ID)),
            WebhostingAccountId::fromString(self::ACCOUNT_ID)
        ));
    }

    private function createCapabilitiesGuard(LogMessages $logMessages, $context): CapabilitiesGuard
    {
        $guardProphecy = $this->prophesize(CapabilitiesGuard::class);
        $guardProphecy->allowedTo(
            WebhostingAccountId::fromString(self::ACCOUNT_ID),
            $context,
            MailboxCountCount::class
        )->willReturn($logMessages);

        return $guardProphecy->reveal();
    }

    private function createUnusedCapabilitiesGuard(): CapabilitiesGuard
    {
        $guardProphecy = $this->prophesize(CapabilitiesGuard::class);
        $guardProphecy->allowedTo(Argument::cetera())->shouldNotBeCalled();

        return $guardProphecy->reveal();
    }
}
