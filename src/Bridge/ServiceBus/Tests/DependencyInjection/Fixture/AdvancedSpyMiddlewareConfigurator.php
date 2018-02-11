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

namespace ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\Middleware;

use ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\AdvancedMiddlewareConfigurator;
use ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\MiddlewaresConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\AbstractServiceConfigurator;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>\
 *
 * @internal
 */
final class AdvancedSpyMiddlewareConfigurator implements AdvancedMiddlewareConfigurator
{
    public static $arguments;
    private $parent;

    public function __construct(MiddlewaresConfigurator $parent, AbstractServiceConfigurator $di, string $serviceId, $argument1, $argument2)
    {
        self::$arguments = [$parent, $di, $serviceId, $argument1, $argument2];
        $this->parent = $parent;
    }

    public function end(): MiddlewaresConfigurator
    {
        return $this->parent;
    }
}
