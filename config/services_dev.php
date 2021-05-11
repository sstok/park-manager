<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Faker\Factory as Faker;
use Faker\Generator as FakerGenerator;

return static function (ContainerConfigurator $c): void {
    $di = $c->services()->defaults()
        ->autowire()
        ->autoconfigure()
        ->private()
        ->bind('$commandBus', service('park_manager.command_bus'));

    $di->load('ParkManager\\DataFixtures\\', __DIR__ . '/../src/DataFixtures');

    $di->set(FakerGenerator::class)
        ->factory([Faker::class, 'create']);
};
