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

namespace ParkManager\Bridge\ServiceBus\Tests\DependencyInjection\Configurator;

use League\Tactician\CommandBus;
use ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\MessageBusConfigurator;
use ParkManager\Bridge\ServiceBus\Tests\Fixtures\Handler\CancelUserHandler;
use ParkManager\Bridge\ServiceBus\Tests\Fixtures\Handler\RegisterUserHandler;
use ParkManager\Bridge\ServiceBus\Tests\Fixtures\Middleware\MessageGuardMiddleware;
use ParkManager\Component\ServiceBus\TacticianCommandBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * @internal
 */
final class MessageBusConfiguratorTest extends TestCase
{
    /** @test */
    public function it_registers_handlers()
    {
        $instanceof = [];
        $containerConfigurator = new ServicesConfigurator(
            $containerBuilder = new ContainerBuilder(),
            new PhpFileLoader($containerBuilder, $this->createMock(FileLocatorInterface::class)),
            $instanceof
        );

        MessageBusConfigurator::register($containerConfigurator->defaults(), 'park_manager.command_bus.users')
            ->handlers()
                ->load('ParkManager\\Bridge\\ServiceBus\\Tests\\Fixtures\\Handler\\', '../../Fixtures/Handler', '../../Fixtures/Handler/CancelUserHandler.php')
            ->end()
            ->handlers();

        $expectedDef = new Definition(TacticianCommandBus::class);
        $expectedDef->addTag('park_manager.service_bus');
        $expectedDef->setPublic(false);

        self::assertEquals($expectedDef, $containerBuilder->getDefinition('park_manager.command_bus.users'));

        $expectedDef = new Definition(RegisterUserHandler::class);
        $expectedDef->addTag('park_manager.command_bus.users.handler');
        $expectedDef->setPublic(false);

        self::assertEquals($expectedDef, $containerBuilder->getDefinition('park_manager.command_bus.users.handler.'.RegisterUserHandler::class));
        self::assertFalse($containerBuilder->hasDefinition('park_manager.command_bus.users.handler.'.CancelUserHandler::class));
    }

    /** @test */
    public function it_registers_middlewares()
    {
        $instanceof = [];
        $containerConfigurator = new ServicesConfigurator(
            $containerBuilder = new ContainerBuilder(),
            new PhpFileLoader($containerBuilder, $this->createMock(FileLocatorInterface::class)),
            $instanceof
        );

        $configurator = MessageBusConfigurator::register($containerConfigurator->defaults(), 'park_manager.command_bus.users');
        $configurator
            ->middlewares()
                ->register(MessageGuardMiddleware::class)
            ->end()
            ->middlewares();

        $expectedDef = new Definition(MessageGuardMiddleware::class);
        $expectedDef->addTag('park_manager.command_bus.users.middleware', ['priority' => 0]);
        $expectedDef->setPublic(false);

        self::assertEquals(
            $expectedDef,
            $containerBuilder->getDefinition('park_manager.command_bus.users.middleware.'.MessageGuardMiddleware::class)
        );
    }

    /** @test */
    public function it_extends_existing()
    {
        $instanceof = [];
        $containerConfigurator = new ServicesConfigurator(
            $containerBuilder = new ContainerBuilder(),
            new PhpFileLoader($containerBuilder, $this->createMock(FileLocatorInterface::class)),
            $instanceof
        );

        MessageBusConfigurator::register($containerConfigurator->defaults(), 'park_manager.command_bus.users');
        MessageBusConfigurator::extend($containerConfigurator->defaults(), 'park_manager.command_bus.users')
            ->handlers()
                ->load('ParkManager\\Bridge\\ServiceBus\\Tests\\Fixtures\\Handler\\', '../../Fixtures/Handler', '../../Fixtures/Handler/CancelUserHandler.php')
            ->end()
            ->handlers();

        $expectedDef = new Definition(RegisterUserHandler::class);
        $expectedDef->addTag('park_manager.command_bus.users.handler');
        $expectedDef->setPublic(false);

        self::assertEquals($expectedDef, $containerBuilder->getDefinition('park_manager.command_bus.users.handler.'.RegisterUserHandler::class));
        self::assertFalse($containerBuilder->hasDefinition('park_manager.command_bus.users.handler.'.CancelUserHandler::class));
    }
}
