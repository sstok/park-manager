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

use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\QueryBusConfigurator;
use ParkManager\Component\ServiceBus\TacticianQueryBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * @internal
 */
final class QueryBusConfiguratorTest extends TestCase
{
    /** @test */
    public function it_registers_query_bus()
    {
        $instanceof = [];
        $containerConfigurator = new ServicesConfigurator(
            $containerBuilder = new ContainerBuilder(),
            new PhpFileLoader($containerBuilder, $this->createMock(FileLocatorInterface::class)),
            $instanceof
        );

        QueryBusConfigurator::register($containerConfigurator->defaults(), 'park_manager.query_bus.users');

        $expectedDef = new Definition(TacticianQueryBus::class);
        $expectedDef->addTag('park_manager.service_bus');
        $expectedDef->setPublic(false);

        self::assertEquals($expectedDef, $containerBuilder->getDefinition('park_manager.query_bus.users'));
    }
}
