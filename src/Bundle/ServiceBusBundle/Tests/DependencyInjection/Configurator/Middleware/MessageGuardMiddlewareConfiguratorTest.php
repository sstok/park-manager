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

namespace ParkManager\Bundle\ServiceBusBundle\Tests\DependencyInjection\Configurator\Middleware;

use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\MessageBusConfigurator;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\Middleware\MessageGuardMiddlewareConfigurator;
use ParkManager\Bundle\ServiceBusBundle\Guard\CliGuard;
use ParkManager\Bundle\ServiceBusBundle\Guard\SymfonyGuard;
use ParkManager\Bundle\ServiceBusBundle\Test\DependencyInjection\MiddlewareConfiguratorTestCase;
use ParkManager\Bundle\ServiceBusBundle\Tests\Fixtures\Guard\FooGuard;
use ParkManager\Component\ServiceBus\MessageGuard\MessageGuardMiddleware;

/**
 * @internal
 */
final class MessageGuardMiddlewareConfiguratorTest extends MiddlewareConfiguratorTestCase
{
    /** @test */
    public function it_registers_middleware()
    {
        new MessageGuardMiddlewareConfigurator(
            $this->containerConfigurator->defaults(),
            'park_manager.command_bus.users'
        );

        $serviceId = 'park_manager.command_bus.users.middleware.message_guard';
        $this->assertContainerBuilderHasService($serviceId, MessageGuardMiddleware::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            $serviceId,
            'park_manager.command_bus.users.middleware',
            ['priority' => MessageBusConfigurator::MIDDLEWARE_PRIORITY_GUARD]
        );
    }

    /** @test */
    public function it_registers_guards()
    {
        new MessageGuardMiddlewareConfigurator(
            $this->containerConfigurator->defaults(),
            'park_manager.command_bus.users',
            CliGuard::class,
            [SymfonyGuard::class, -5],
            [FooGuard::class, 0, ['bar']]
        );

        $serviceId = 'park_manager.command_bus.users.middleware.message_guard';
        $this->assertContainerBuilderHasService($serviceId, MessageGuardMiddleware::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            $serviceId,
            'park_manager.command_bus.users.middleware',
            ['priority' => MessageBusConfigurator::MIDDLEWARE_PRIORITY_GUARD]
        );

        $serviceId = 'park_manager.command_bus.users.message_guard.'.CliGuard::class;
        $this->assertContainerBuilderHasService($serviceId, CliGuard::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            $serviceId,
            'park_manager.command_bus.users.message_guard',
            ['priority' => 0]
        );

        $serviceId = 'park_manager.command_bus.users.message_guard.'.SymfonyGuard::class;
        $this->assertContainerBuilderHasService($serviceId, SymfonyGuard::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            $serviceId,
            'park_manager.command_bus.users.message_guard',
            ['priority' => -5]
        );

        $serviceId = 'park_manager.command_bus.users.message_guard.'.FooGuard::class;
        $this->assertContainerBuilderHasService($serviceId, FooGuard::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, 0, 'bar');
        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            $serviceId,
            'park_manager.command_bus.users.message_guard',
            ['priority' => 0]
        );
    }
}
