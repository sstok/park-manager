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

namespace ParkManager\Bridge\ServiceBus\Test\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractContainerBuilderTestCase;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
abstract class MiddlewareConfiguratorTestCase extends AbstractContainerBuilderTestCase
{
    /**
     * @var ServicesConfigurator
     */
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
