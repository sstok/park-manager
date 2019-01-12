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

namespace ParkManager\Module\WebhostingModule\Tests\Infrastructure\ServiceBus\Middleware;

use ParkManager\Component\ApplicationFoundation\Message\ServiceMessages;
use ParkManager\Module\WebhostingModule\Infrastructure\Service\Package\CapabilitiesRestrictionGuard;
use ParkManager\Module\WebhostingModule\Infrastructure\ServiceBus\Middleware\AccountCapabilitiesRestrictionGuardMiddleware;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Application\Mailbox\CreateMailbox;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Application\Mailbox\RemoveMailbox;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Application\Package\CreatePackage;
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
        $serviceMessages = new ServiceMessages();
        $middleware      = new AccountCapabilitiesRestrictionGuardMiddleware(
            $this->createCapabilitiesGuard($serviceMessages),
            $serviceMessages
        );

        self::assertTrue($middleware->execute(
            $command = new CreatePackage(),
            static function () { return true; }
        ));
    }

    /** @test */
    public function it_returns_false_when_guard_decides_to_block()
    {
        $serviceMessages = new ServiceMessages();
        $middleware      = new AccountCapabilitiesRestrictionGuardMiddleware(
            $this->createCapabilitiesGuard($serviceMessages),
            $serviceMessages
        );

        self::assertFalse($middleware->execute(
            $command = new CreateMailbox(self::ACCOUNT_ID, 500),
            static function () { return true; }
        ));
    }

    /** @test */
    public function it_continues_execution_when_guard_approves()
    {
        $serviceMessages = new ServiceMessages();
        $middleware      = new AccountCapabilitiesRestrictionGuardMiddleware(
            $this->createCapabilitiesGuard($serviceMessages),
            $serviceMessages
        );

        self::assertEquals('it-worked', $middleware->execute(
            $command = new RemoveMailbox(self::ACCOUNT_ID),
            static function ($passedCommand) use ($command) {
                self::assertSame($command, $passedCommand);

                return 'it-worked';
            }
        ));
    }

    private function createCapabilitiesGuard(ServiceMessages $serviceMessages): CapabilitiesRestrictionGuard
    {
        $guardProphecy = $this->prophesize(CapabilitiesRestrictionGuard::class);
        $guardProphecy->decide(
            Argument::type(CreateMailbox::class),
            $serviceMessages
        )->willReturn(false);

        $guardProphecy->decide(
            Argument::type(RemoveMailbox::class),
            $serviceMessages
        )->willReturn(true);

        $guardProphecy->decide(
            Argument::type(CreatePackage::class),
            $serviceMessages
        )->shouldNotBeCalled();

        return $guardProphecy->reveal();
    }
}
