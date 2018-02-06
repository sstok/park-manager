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

use ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\MessageBusConfigurator;
use ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\MiddlewaresConfigurator;
use ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\Plugin\AdvancedSpyMiddlewaresConfigurator;
use ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\Plugin\SpyMiddlewaresConfigurator;
use ParkManager\Bridge\ServiceBus\Tests\Fixtures\Middleware\MessageGuardMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\AbstractServiceConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

require_once __DIR__.'/../Fixture/SpyMiddlewaresConfigurator.php';
require_once __DIR__.'/../Fixture/AdvancedSpyMiddlewaresConfigurator.php';

/**
 * @internal
 */
final class MiddlewaresConfiguratorTest extends TestCase
{
    /** @test */
    public function it_fails_if_class_cannot_be_located()
    {
        $servicesConfigurator = $this->createMock(AbstractServiceConfigurator::class);

        $busConfigurator = $this->createMock(MessageBusConfigurator::class);
        $configurator = new MiddlewaresConfigurator($busConfigurator, $servicesConfigurator, 'park_manager.command_bus.users');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot locate class "ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\Plugin\NopeMiddlewaresConfigurator" for plugin nope.');

        $configurator->nope();
    }

    /** @test */
    public function it_invokes_middleware_configurator()
    {
        $servicesConfigurator = $this->createMock(AbstractServiceConfigurator::class);
        $busConfigurator = $this->createMock(MessageBusConfigurator::class);
        $configurator = new MiddlewaresConfigurator($busConfigurator, $servicesConfigurator, 'park_manager.command_bus.users');

        SpyMiddlewaresConfigurator::$arguments = null;

        self::assertSame($configurator, $configurator->spy('hello', 'foobar'));
        self::assertSame($busConfigurator, $configurator->end());
        self::assertEquals(
            [$servicesConfigurator, 'park_manager.command_bus.users', 'hello', 'foobar'],
            SpyMiddlewaresConfigurator::$arguments
        );
    }

    /** @test */
    public function it_invokes_advanced_middleware_configurator()
    {
        $servicesConfigurator = $this->createMock(AbstractServiceConfigurator::class);
        $busConfigurator = $this->createMock(MessageBusConfigurator::class);
        $configurator = new MiddlewaresConfigurator($busConfigurator, $servicesConfigurator, 'park_manager.command_bus.users');

        AdvancedSpyMiddlewaresConfigurator::$arguments = null;

        self::assertInstanceOf(AdvancedSpyMiddlewaresConfigurator::class, $configurator->advancedSpy('hello', 'foobar'));
        self::assertEquals(
            [$configurator, $servicesConfigurator, 'park_manager.command_bus.users', 'hello', 'foobar'],
            AdvancedSpyMiddlewaresConfigurator::$arguments
        );
    }

    /** @test */
    public function it_registers_standalone_middleware_service()
    {
        $instanceof = [];
        $containerConfigurator = new ServicesConfigurator(
            $containerBuilder = new ContainerBuilder(),
            new PhpFileLoader($containerBuilder, $this->createMock(FileLocatorInterface::class)),
            $instanceof
        );

        $busConfigurator = $this->createMock(MessageBusConfigurator::class);
        $configurator = new MiddlewaresConfigurator($busConfigurator, $containerConfigurator->defaults(), 'park_manager.command_bus.users');
        $configurator->register(MessageGuardMiddleware::class, 5, ['foo']);

        $expectedDef = new Definition(MessageGuardMiddleware::class);
        $expectedDef->addTag('park_manager.command_bus.users.middleware', ['priority' => 5]);
        $expectedDef->setArguments(['foo']);
        $expectedDef->setPublic(false);

        self::assertEquals(
            $expectedDef,
            $containerBuilder->getDefinition('park_manager.command_bus.users.middleware.'.MessageGuardMiddleware::class)
        );
    }
}
