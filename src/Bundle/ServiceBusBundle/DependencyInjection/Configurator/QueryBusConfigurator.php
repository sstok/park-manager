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

namespace ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator;

use ParkManager\Component\ServiceBus\TacticianQueryBus;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;

/**
 * @final
 */
class QueryBusConfigurator extends MessageBusConfigurator
{
    public static function register(DefaultsConfigurator $di, string $serviceId): MessageBusConfigurator
    {
        $serviceBus = $di->set($serviceId, TacticianQueryBus::class)->private();
        $serviceBus->tag('park_manager.service_bus');

        return new static($di, $serviceId);
    }
}
