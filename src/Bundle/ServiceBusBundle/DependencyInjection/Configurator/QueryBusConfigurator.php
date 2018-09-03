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
