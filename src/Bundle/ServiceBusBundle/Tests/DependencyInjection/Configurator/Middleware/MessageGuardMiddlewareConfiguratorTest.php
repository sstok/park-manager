<?php

declare(strict_types=1);

/*
 * This file is part of the Park-Manager project.
 *
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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
