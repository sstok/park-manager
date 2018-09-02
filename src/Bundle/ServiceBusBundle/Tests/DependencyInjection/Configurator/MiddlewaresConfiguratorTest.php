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

namespace ParkManager\Bundle\ServiceBusBundle\Tests\DependencyInjection\Configurator;

use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\MessageBusConfigurator;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\Middleware\AdvancedSpyMiddlewareConfigurator;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\Middleware\SpyMiddlewareConfigurator;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\MiddlewaresConfigurator;
use ParkManager\Bundle\ServiceBusBundle\Tests\Fixtures\Middleware\MessageGuardMiddleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\AbstractServiceConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

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
        $this->expectExceptionMessage('Cannot locate class "ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\Middleware\NopeMiddlewareConfigurator" for middleware nope.');

        $configurator->nope();
    }

    /** @test */
    public function it_invokes_middleware_configurator()
    {
        $servicesConfigurator = $this->createMock(AbstractServiceConfigurator::class);
        $busConfigurator = $this->createMock(MessageBusConfigurator::class);
        $configurator = new MiddlewaresConfigurator($busConfigurator, $servicesConfigurator, 'park_manager.command_bus.users');

        SpyMiddlewareConfigurator::$arguments = null;

        self::assertSame($configurator, $configurator->spy('hello', 'foobar'));
        self::assertSame($busConfigurator, $configurator->end());
        self::assertEquals(
            [$servicesConfigurator, 'park_manager.command_bus.users', 'hello', 'foobar'],
            SpyMiddlewareConfigurator::$arguments
        );
    }

    /** @test */
    public function it_invokes_advanced_middleware_configurator()
    {
        $servicesConfigurator = $this->createMock(AbstractServiceConfigurator::class);
        $busConfigurator = $this->createMock(MessageBusConfigurator::class);
        $configurator = new MiddlewaresConfigurator($busConfigurator, $servicesConfigurator, 'park_manager.command_bus.users');

        AdvancedSpyMiddlewareConfigurator::$arguments = null;

        self::assertInstanceOf(AdvancedSpyMiddlewareConfigurator::class, $configurator->advancedSpy('hello', 'foobar'));
        self::assertEquals(
            [$configurator, $servicesConfigurator, 'park_manager.command_bus.users', 'hello', 'foobar'],
            AdvancedSpyMiddlewareConfigurator::$arguments
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
