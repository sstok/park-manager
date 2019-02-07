<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use League\Tactician\Plugins\LockingMiddleware;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\MessageBusConfigurator;
use ParkManager\Module\WebhostingModule\Infrastructure\ServiceBus\Middleware\AccountCapabilitiesRestrictionGuardMiddleware;

return function (ContainerConfigurator $c) {
    $di = $c->services()->defaults()
        ->autowire()
        ->autoconfigure()
        ->private();

    MessageBusConfigurator::register($di, 'park_manager.command_bus.webhosting')
        ->middlewares()
            ->register(LockingMiddleware::class)
            ->doctrineOrmTransaction('default')
            ->domainEvents()
            ->end()
            ->register(AccountCapabilitiesRestrictionGuardMiddleware::class, -100)
        ->end()
        ->handlers(__DIR__ . '/../../../../Application')
            ->load('ParkManager\\Module\\WebhostingModule\\Application\\', '**/*Handler.php')
        ->end();
};
