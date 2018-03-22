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
use ParkManager\Component\Model\Event\EventEmitter;
use ParkManager\Module\Webhosting\Domain\Package\{
    CapabilitiesFactory,
    CapabilitiesGuard,
    Capability\MonthlyTrafficQuota,
    WebhostingPackageRepository
};
use ParkManager\Module\Webhosting\Infrastructure\Doctrine\Package\WebhostingPackageOrmRepository;
use ParkManager\Module\Webhosting\Infrastructure\Service\PackageCapability\MonthlyTrafficQuotaApplier;
use ParkManager\Module\Webhosting\Service\Package\{AccountCapabilitiesGuard, CapabilitiesRegistry};
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

return function (ContainerConfigurator $c) {
    $di = $c->services()->defaults()
        ->autowire()
        ->autoconfigure()
        // Bindings
        ->bind(EventEmitter::class, ref('park_manager.command_bus.webhosting.domain_event_emitter'))
        ->bind(EntityManagerInterface::class, ref('doctrine.orm.entity_manager'));

    // CapabilitiesFactory alias needs to be public for Doctrine type in ParkManagerWebhostingBundle::boot()
    $di->set(CapabilitiesRegistry::class)
        ->alias(CapabilitiesFactory::class, CapabilitiesRegistry::class)->public();

    $di->set(AccountCapabilitiesGuard::class)
        ->alias(CapabilitiesGuard::class, AccountCapabilitiesGuard::class);

    $di->set(WebhostingPackageOrmRepository::class)
        ->alias(WebhostingPackageRepository::class, WebhostingPackageOrmRepository::class);

    $di->load('ParkManager\Module\Webhosting\Infrastructure\\Service\\PackageCapability\\',
        __DIR__.'/../../../../Infrastructure/Service/PackageCapability'
    );

    $di->set(MonthlyTrafficQuota::class)->tag('park_manager.webhosting_capability', [
        'applier' => MonthlyTrafficQuotaApplier::class,
        'form-type' => IntegerType::class,
    ]);
};
