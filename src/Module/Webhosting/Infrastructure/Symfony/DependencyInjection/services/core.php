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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Module\Webhosting\Infrastructure\Doctrine\Repository\{
    WebhostingAccountOrmRepository, WebhostingDomainNameOrmRepository, WebhostingPackageOrmRepository
};
use ParkManager\Module\Webhosting\Model\{
    Account\WebhostingAccountRepository,
    DomainName\WebhostingDomainNameRepository,
    Package\CapabilitiesFactory
};
use ParkManager\Module\Webhosting\Service\Package\CapabilitiesManager;
use Prooph\ServiceBus\EventBus;

return function (ContainerConfigurator $c) {
    $di = $c->services()->defaults()
        ->autowire()
        ->autoconfigure()
        // Bindings
        ->bind(EventBus::class, ref('prooph_service_bus.webhosting.event_bus'))
        ->bind(EntityManagerInterface::class, ref('doctrine.orm.entity_manager'))
    ;

    // alias needs to be public for Doctrine type in ParkManagerWebhostingBundle::boot()
    $di->set(CapabilitiesManager::class)
        ->alias(CapabilitiesFactory::class, CapabilitiesManager::class)->public();

    $di->set(WebhostingDomainNameOrmRepository::class)
        ->alias(WebhostingDomainNameRepository::class, WebhostingDomainNameOrmRepository::class);
    $di->set(WebhostingAccountOrmRepository::class)
        ->alias(WebhostingAccountRepository::class, WebhostingAccountOrmRepository::class);
    $di->set(WebhostingPackageOrmRepository::class)
        ->alias(WebhostingAccountRepository::class, WebhostingAccountOrmRepository::class);

    $di->load('ParkManager\Module\Webhosting\Model\\', __DIR__.'/../../../../Model/{Account,DomainName,Package}/Handler')
        ->tag('prooph_service_bus.webhosting.command_bus.route_target', ['message_detection' => true])
        ->public();
};
