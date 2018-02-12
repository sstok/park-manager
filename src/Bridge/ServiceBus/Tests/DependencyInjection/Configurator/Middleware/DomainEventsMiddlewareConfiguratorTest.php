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

namespace ParkManager\Bridge\ServiceBus\Tests\DependencyInjection\Configurator\Middleware;

use ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\MessageBusConfigurator;
use ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\Middleware\DomainEventsMiddlewareConfigurator;
use ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\MiddlewaresConfigurator;
use ParkManager\Bridge\ServiceBus\Test\DependencyInjection\MiddlewareConfiguratorTestCase;
use ParkManager\Bridge\ServiceBus\Tests\DependencyInjection\Fixture\EventListener\RegisterAdminSubscriber;
use ParkManager\Bridge\ServiceBus\Tests\DependencyInjection\Fixture\EventListener\RegisterUserListener;
use ParkManager\Component\Model\Event\SymfonyEventEmitter;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
final class DomainEventsMiddlewareConfiguratorTest extends MiddlewareConfiguratorTestCase
{
    /** @test */
    public function it_registers_middleware()
    {
        $di = $this->containerConfigurator->defaults();
        $configurator = $this->createConfigurator($di);

        $busId = 'park_manager.command_bus.users';
        $this->assertContainerBuilderHasService($busId.'.domain_event_emitter.symfony', EventDispatcher::class);
        $this->assertContainerBuilderHasService($busId.'.domain_event_emitter', SymfonyEventEmitter::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag($busId.'.domain_event_emitter', 'park_manager.service_bus.domain_event_emitter', ['bus-id' => $busId]);
    }

    /** @test */
    public function it_registers_subscribers_and_listeners()
    {
        $di = $this->containerConfigurator->defaults();
        $configurator = $this->createConfigurator($di);

        $configurator->listener(
            RegisterUserListener::class,
            [
                ['event' => 'registerUser', 'method' => 'onRegisterUser'],
                ['event' => 'removeUser', 'method' => 'onRemoveUser'],
            ],
            ['bar']
        );
        $configurator->subscriber(RegisterAdminSubscriber::class, ['foo']);

        $busId = 'park_manager.command_bus.users';

        $this->assertContainerBuilderHasServiceDefinitionWithTag($busId.'.domain_event_emitter', 'park_manager.service_bus.domain_event_emitter', ['bus-id' => $busId]);

        $this->assertContainerBuilderHasService($busId.'.domain_event_listener.'.RegisterAdminSubscriber::class);
        $this->assertContainerBuilderHasService($busId.'.domain_event_listener.'.RegisterUserListener::class);

        $expectedDef = (new Definition(RegisterAdminSubscriber::class))->setPublic(false);
        $expectedDef->addTag($busId.'.domain_event_subscriber');
        $expectedDef->setArguments(['foo']);
        self::assertEquals($expectedDef, $this->container->getDefinition($busId.'.domain_event_listener.'.RegisterAdminSubscriber::class));

        $expectedDef = (new Definition(RegisterUserListener::class))->setPublic(false);
        $expectedDef->addTag($busId.'.domain_event_listener', ['event' => 'registerUser', 'method' => 'onRegisterUser']);
        $expectedDef->addTag($busId.'.domain_event_listener', ['event' => 'removeUser', 'method' => 'onRemoveUser']);
        $expectedDef->setArguments(['bar']);
        self::assertEquals($expectedDef, $this->container->getDefinition($busId.'.domain_event_listener.'.RegisterUserListener::class));
    }

    private function createConfigurator(DefaultsConfigurator $di): DomainEventsMiddlewareConfigurator
    {
        $serviceId = 'park_manager.command_bus.users';
        $configurator = new DomainEventsMiddlewareConfigurator(
            $midConfigurator = new MiddlewaresConfigurator(MessageBusConfigurator::extend($di, $serviceId), $di, $serviceId),
            $di,
            $serviceId
        );

        self::assertSame($midConfigurator, $configurator->end());

        return $configurator;
    }

    //
//    /** @test */
//    public function it_registers_guards()
//    {
//        new DomainEventsMiddlewareConfigurator(
//            $this->containerConfigurator->defaults(),
//            'park_manager.command_bus.users',
//            CliGuard::class,
//            [SymfonyGuard::class, -5],
//            [FooGuard::class, 0, ['bar']]
//        );
//
//        $serviceId = 'park_manager.command_bus.users.middleware.message_guard';
//        $this->assertContainerBuilderHasService($serviceId, MessageGuardMiddleware::class);
//        $this->assertContainerBuilderHasServiceDefinitionWithTag(
//            $serviceId,
//            'park_manager.command_bus.users.middleware',
//            ['priority' => 5000]
//        );
//
//        $serviceId = 'park_manager.command_bus.users.message_guard.'.CliGuard::class;
//        $this->assertContainerBuilderHasService($serviceId, CliGuard::class);
//        $this->assertContainerBuilderHasServiceDefinitionWithTag(
//            $serviceId,
//            'park_manager.command_bus.users.message_guard',
//            ['priority' => 0]
//        );
//
//        $serviceId = 'park_manager.command_bus.users.message_guard.'.SymfonyGuard::class;
//        $this->assertContainerBuilderHasService($serviceId, SymfonyGuard::class);
//        $this->assertContainerBuilderHasServiceDefinitionWithTag(
//            $serviceId,
//            'park_manager.command_bus.users.message_guard',
//            ['priority' => -5]
//        );
//
//        $serviceId = 'park_manager.command_bus.users.message_guard.'.FooGuard::class;
//        $this->assertContainerBuilderHasService($serviceId, FooGuard::class);
//        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, 0, 'bar');
//        $this->assertContainerBuilderHasServiceDefinitionWithTag(
//            $serviceId,
//            'park_manager.command_bus.users.message_guard',
//            ['priority' => 0]
//        );
//    }
}
