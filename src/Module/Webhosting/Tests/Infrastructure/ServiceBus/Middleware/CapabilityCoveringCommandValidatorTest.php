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

namespace ParkManager\Module\Webhosting\Tests\Infrastructure\ServiceBus\Middleware;

use ParkManager\Component\Model\LogMessage\LogMessage;
use ParkManager\Component\Model\LogMessage\LogMessages;
use ParkManager\Module\Webhosting\Domain\Account\WebhostingAccountId;
use ParkManager\Module\Webhosting\Domain\Package\CapabilitiesGuard;
use ParkManager\Module\Webhosting\Infrastructure\ServiceBus\Middleware\CapabilityCoveringCommandValidator;
use ParkManager\Module\Webhosting\Tests\Fixtures\Application\Mailbox\{CreateMailbox, RemoveMailbox};
use ParkManager\Module\Webhosting\Tests\Fixtures\Application\Package\CreatePackage;
use ParkManager\Module\Webhosting\Tests\Fixtures\Domain\PackageCapability\MailboxCountCount;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @internal
 */
final class CapabilityCoveringCommandValidatorTest extends TestCase
{
    private const ACCOUNT_ID = '2d3fb900-a528-11e7-a027-acbc32b58315';

    /** @test */
    public function it_ignores_unsupported_commands()
    {
        $commandGuard = new CapabilityCoveringCommandValidator($this->createUnusedCapabilitiesGuard(), $logStack = new LogMessages());

        self::assertTrue($commandGuard->execute(
            $command = new CreatePackage(),
            function () { return true; }
        ));
        self::assertEquals(new LogMessages(), $logStack);
    }

    /** @test */
    public function it_maps_command_to_capabilities()
    {
        $logMessages = new LogMessages();
        $logMessages->add(LogMessage::error('Cannot let you do this John, your mailbox limit is reached.'));

        $commandGuard = new CapabilityCoveringCommandValidator($this->createCapabilitiesGuard($logMessages, []), $logStack = new LogMessages());

        self::assertFalse($commandGuard->execute(
            $command = new CreateMailbox(WebhostingAccountId::fromString(self::ACCOUNT_ID), 500),
            function () { return true; }
        ));
        self::assertEquals($logMessages, $logStack);
    }

    /** @test */
    public function it_continues_execution_when_guard_approves()
    {
        $commandGuard = new CapabilityCoveringCommandValidator($this->createCapabilitiesGuard(new LogMessages(), []), $logStack = new LogMessages());

        self::assertEquals('it-worked', $commandGuard->execute(
            $command = new CreateMailbox(WebhostingAccountId::fromString(self::ACCOUNT_ID), 500),
            function ($passedCommand) use ($command) {
                self::assertSame($command, $passedCommand);

                return 'it-worked';
            }
        ));
        self::assertEquals(new LogMessages(), $logStack);
    }

    /** @test */
    public function it_maps_command_to_capabilities_with_context()
    {
        $logMessages = new LogMessages();
        $logMessages->add(LogMessage::error('Cannot let you do this John, your mailbox limit is reached.'));

        $commandGuard = new CapabilityCoveringCommandValidator(
            $this->createCapabilitiesGuard($logMessages, ['account' => self::ACCOUNT_ID]),
            $logStack = new LogMessages(),
            function ($command, $account) {
                return ['account' => (string) $account];
            }
        );

        self::assertFalse($commandGuard->execute(
            $command = new CreateMailbox(WebhostingAccountId::fromString(self::ACCOUNT_ID), 500),
            function () { return true; }
        ));
        self::assertEquals($logMessages, $logStack);

        self::assertTrue($commandGuard->execute(
            $command = new RemoveMailbox(WebhostingAccountId::fromString(self::ACCOUNT_ID)),
            function () { return true; }
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
