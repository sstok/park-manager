<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ParkManager\Bundle\CoreBundle\DependencyInjection\AutoServiceConfigurator;
use ParkManager\Bundle\WebhostingBundle\Doctrine\Account\WebhostingAccountOrmRepository;
use ParkManager\Bundle\WebhostingBundle\Doctrine\DomainName\WebhostingDomainNameOrmRepository;
use ParkManager\Bundle\WebhostingBundle\Doctrine\Plan\CapabilitiesTypeConfigurator;
use ParkManager\Bundle\WebhostingBundle\Doctrine\Plan\WebhostingPlanOrmRepository;
use ParkManager\Bundle\WebhostingBundle\Plan\CapabilitiesFactory;

return function (ContainerConfigurator $c) {
    $di = $c->services()->defaults()
        ->autowire()
        ->autoconfigure()
        ->private();

    $autoDi = new AutoServiceConfigurator($di);

    $di->load('ParkManager\\Bundle\\WebhostingBundle\\', __DIR__ . '/../src/*')
        ->exclude([
            __DIR__ . '/../src/{DependencyInjection,Model,Test,UseCase,DataFixtures}',
            __DIR__ . '/../src/Doctrine/*/{Type}',
        ]);

    $di->load('ParkManager\\Bundle\\WebhostingBundle\\UseCase\\', __DIR__ . '/../src/UseCase/**/*Handler.php')
        ->tag('messenger.message_handler', ['bus' => 'park_manager.command_bus']);

    // CapabilitiesFactory alias needs to be public for Doctrine type in ParkManagerWebhostingModule::boot()
    $di->set(CapabilitiesFactory::class)->arg(0, []);
    $di->set(CapabilitiesTypeConfigurator::class);

    $autoDi->set(WebhostingAccountOrmRepository::class)
        ->configurator(ref(CapabilitiesTypeConfigurator::class));

    $autoDi->set(WebhostingDomainNameOrmRepository::class);

    $autoDi->set(WebhostingPlanOrmRepository::class)
        ->configurator(ref(CapabilitiesTypeConfigurator::class));
};
