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

namespace ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\Middleware;

use League\Tactician\Doctrine\DBAL\TransactionMiddleware;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\MessageBusConfigurator;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\MiddlewareConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\AbstractServiceConfigurator;
use Symfony\Component\DependencyInjection\Reference;

final class DoctrineDbalTransactionMiddlewareConfigurator implements MiddlewareConfigurator
{
    public function __construct(AbstractServiceConfigurator $di, string $serviceId, string $managerName)
    {
        $di->set($serviceId.'.middleware.doctrine_transaction', TransactionMiddleware::class)
            ->args([new Reference('doctrine.dbal.default'.$managerName.'_connection')])
            ->tag($serviceId.'.middleware', ['priority' => MessageBusConfigurator::MIDDLEWARE_PRIORITY_TRANSACTION])
            ->private();
    }
}
