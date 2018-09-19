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

use League\Tactician\Doctrine\DBAL\TransactionMiddleware;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\MessageBusConfigurator;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\MiddlewareConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\AbstractServiceConfigurator;
use Symfony\Component\DependencyInjection\Reference;

final class DoctrineDbalTransactionMiddlewareConfigurator implements MiddlewareConfigurator
{
    public function __construct(AbstractServiceConfigurator $di, string $serviceId, string $managerName)
    {
        $di->set($serviceId . '.middleware.doctrine_transaction', TransactionMiddleware::class)
            ->args([new Reference('doctrine.dbal.default' . $managerName . '_connection')])
            ->tag($serviceId . '.middleware', ['priority' => MessageBusConfigurator::MIDDLEWARE_PRIORITY_TRANSACTION])
            ->private();
    }
}
