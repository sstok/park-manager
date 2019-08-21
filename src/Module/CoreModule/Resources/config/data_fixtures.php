<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ParkManager\Module\CoreModule\Doctrine\Shared\DoctrineDbalAuthenticationFinder;
use ParkManager\Module\CoreModule\Http\ArgumentResolver\FormFactoryResolver;
use Rollerworks\Component\SplitToken\Argon2SplitTokenFactory;
use ParkManager\Module\CoreModule\DependencyInjection\AutoServiceConfigurator;
use ParkManager\Module\CoreModule\Doctrine\Administrator\DoctrineOrmAdministratorRepository;
use ParkManager\Module\CoreModule\Doctrine\Client\DoctrineOrmClientRepository;
use ParkManager\Module\CoreModule\Http\ArgumentResolver\ApplicationContextResolver;
use ParkManager\Module\CoreModule\Http\SectionsLoader;
use ParkManager\Module\CoreModule\Common\ApplicationContext;
use ParkManager\Module\CoreModule\EventListener\ApplicationSectionListener;

return function (ContainerConfigurator $c) {
    $di = $c->services()->defaults()
        ->autowire()
        ->autoconfigure()
        ->private()
        ->bind('$commandBus', ref('park_manager.command_bus'));

    $di->load('ParkManager\\Module\\CoreModule\\DataFixtures\\', __DIR__ . '/../../DataFixtures');
};
