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

namespace ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\Middleware;

use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\MiddlewareConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\AbstractServiceConfigurator;

/**
 * @internal
 */
final class SpyMiddlewareConfigurator implements MiddlewareConfigurator
{
    public static $arguments;

    public function __construct(AbstractServiceConfigurator $di, string $serviceId, $argument1, $argument2)
    {
        self::$arguments = [$di, $serviceId, $argument1, $argument2];
    }
}
