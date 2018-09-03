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
use ParkManager\Component\DomainEvent\EventEmitter;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackageRepository;
use ParkManager\Module\WebhostingModule\Infrastructure\Doctrine\Package\WebhostingPackageOrmRepository;
use ParkManager\Module\WebhostingModule\Infrastructure\Service\Package\{
    AccountCapabilitiesRestrictionGuard,
    CapabilitiesFactory,
    CapabilitiesRestrictionGuard
};

return function (ContainerConfigurator $c) {
    $di = $c->services()->defaults()
        ->autowire()
        ->autoconfigure()
        ->private()
        // Bindings
        ->bind(EventEmitter::class, ref('park_manager.command_bus.webhosting.domain_event_emitter'))
        ->bind(EntityManagerInterface::class, ref('doctrine.orm.entity_manager'));

    // CapabilitiesFactory alias needs to be public for Doctrine type in ParkManagerWebhostingModule::boot()
    $di->set(CapabilitiesFactory::class)->arg(0, [])->public();

    $di->set(AccountCapabilitiesRestrictionGuard::class)
        ->arg('$capabilityGuards', ref('park_manager.webhosting.package_capability_guards'))
        ->arg('$mappings', '%park_manager.webhosting.package_capabilities.command_mapping%');
    $di->alias(CapabilitiesRestrictionGuard::class, AccountCapabilitiesRestrictionGuard::class);

    $di->set(WebhostingPackageOrmRepository::class)
        ->alias(WebhostingPackageRepository::class, WebhostingPackageOrmRepository::class);

    $di->load('ParkManager\\Module\\WebhostingModule\\Domain\\Package\\Capability\\',
        __DIR__.'/../../../../Domain/Package/Capability'
    );
    $di->load('ParkManager\Module\WebhostingModule\Infrastructure\\Service\\Package\\Capability\\',
        __DIR__.'/../../../../Infrastructure/Service/Package/Capability'
    );
};
