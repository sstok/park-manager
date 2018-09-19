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

namespace ParkManager\Bundle\ServiceBusBundle\Test\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractContainerBuilderTestCase;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

abstract class MiddlewareConfiguratorTestCase extends AbstractContainerBuilderTestCase
{
    /** @var ServicesConfigurator */
    protected $containerConfigurator;

    protected function setUp()
    {
        parent::setUp();

        $instanceof = [];

        $this->containerConfigurator = new ServicesConfigurator(
            $this->container,
            new PhpFileLoader($this->container, $this->createMock(FileLocatorInterface::class)),
            $instanceof
        );
    }
}
