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

namespace ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\Plugin;

use ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\MiddlewareConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\AbstractServiceConfigurator;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 *
 * @internal
 */
final class SpyMiddlewaresConfigurator implements MiddlewareConfigurator
{
    public static $arguments;

    public function __construct(AbstractServiceConfigurator $di, string $serviceId, $argument1, $argument2)
    {
        self::$arguments = [$di, $serviceId, $argument1, $argument2];
    }
}
