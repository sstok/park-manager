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
use League\Tactician\Plugins\LockingMiddleware;
use ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\MessageBusConfigurator;
use ParkManager\Component\Model\Event\EventEmitter;
use ParkManager\Module\Webhosting\Infrastructure\Doctrine\Repository\{
    WebhostingAccountOrmRepository, WebhostingDomainNameOrmRepository, WebhostingPackageOrmRepository
};
use ParkManager\Module\Webhosting\Model\{
    Account\WebhostingAccountRepository,
    DomainName\WebhostingDomainNameRepository,
    Package\CapabilitiesFactory,
    Package\CapabilitiesGuard,
    Package\WebhostingPackageRepository
};
use ParkManager\Module\Webhosting\Service\Package\{
    AccountCapabilitiesGuard,
    CapabilitiesRegistry,
    CommandToCapabilitiesGuard
};

return function (ContainerConfigurator $c) {
    $di = $c->services()->defaults()
        ->autowire()
        ->autoconfigure()
        // Bindings
        ->bind(EventEmitter::class, ref('park_manager.command_bus.webhosting.domain_event_emitter'))
        ->bind(EntityManagerInterface::class, ref('doctrine.orm.entity_manager'));

    MessageBusConfigurator::register($di, 'park_manager.command_bus.webhosting')
        ->middlewares()
            ->register(LockingMiddleware::class)
            ->doctrineOrmTransaction('default')
            ->domainEvents()
            ->end()
        ->end()
        ->handlers(__DIR__.'/../../../../Model/')
            ->load('ParkManager\Module\Webhosting\Model\\', '{Account,DomainName,Package}/Handler')
        ->end();

    // CapabilitiesFactory alias needs to be public for Doctrine type in ParkManagerWebhostingBundle::boot()
    $di->set(CapabilitiesRegistry::class)
        ->alias(CapabilitiesFactory::class, CapabilitiesRegistry::class)->public();

    $di->set(AccountCapabilitiesGuard::class)
        ->alias(CapabilitiesGuard::class, AccountCapabilitiesGuard::class);
    $di->set(CommandToCapabilitiesGuard::class)->arg('$commandToCapabilitiesMapping', []);

    $di->set(WebhostingDomainNameOrmRepository::class)
        ->alias(WebhostingDomainNameRepository::class, WebhostingDomainNameOrmRepository::class);
    $di->set(WebhostingAccountOrmRepository::class)
        ->alias(WebhostingAccountRepository::class, WebhostingAccountOrmRepository::class);
    $di->set(WebhostingPackageOrmRepository::class)
        ->alias(WebhostingPackageRepository::class, WebhostingPackageOrmRepository::class);

    $di->load('ParkManager\Module\Webhosting\Infrastructure\\Package\\Capability\\', __DIR__.'/../../../../Infrastructure/Package/Capability');
    $di->load('ParkManager\Module\Webhosting\Model\\Package\\Capability\\', __DIR__.'/../../../../Model/Package/Capability')
        ->tag('park_manager.webhosting_capability');
};
