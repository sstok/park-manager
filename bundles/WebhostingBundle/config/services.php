<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ParkManager\Bundle\WebhostingBundle\Package\CapabilitiesFactory;

return function (ContainerConfigurator $c) {
    $di = $c->services()->defaults()
        ->autowire()
        ->autoconfigure()
        ->private();

    // CapabilitiesFactory alias needs to be public for Doctrine type in ParkManagerWebhostingModule::boot()
    $di->set(CapabilitiesFactory::class)->arg(0, [])->public();

    $di->load('ParkManager\\Bundle\\WebhostingBundle\\', __DIR__ . '/../src/*')
        ->exclude([
            __DIR__ . '/../src/{DependencyInjection,Model,Test,UseCase,DataFixtures}',
            __DIR__ . '/../src/Doctrine/*/{Type}',
        ]);

    $di->load('ParkManager\\Bundle\\WebhostingBundle\\UseCase\\', __DIR__ . '/../src/UseCase/**/*Handler.php')
        ->tag('messenger.message_handler', ['bus' => 'park_manager.command_bus']);
};
