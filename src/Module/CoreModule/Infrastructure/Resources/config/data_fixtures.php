<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ParkManager\Module\CoreModule\Infrastructure\Doctrine\Shared\DoctrineDbalAuthenticationFinder;
use ParkManager\Module\CoreModule\Infrastructure\Http\ArgumentResolver\FormFactoryResolver;
use Rollerworks\Component\SplitToken\Argon2SplitTokenFactory;
use ParkManager\Module\CoreModule\Infrastructure\DependencyInjection\AutoServiceConfigurator;
use ParkManager\Module\CoreModule\Infrastructure\Doctrine\Administrator\DoctrineOrmAdministratorRepository;
use ParkManager\Module\CoreModule\Infrastructure\Doctrine\Client\DoctrineOrmClientRepository;
use ParkManager\Module\CoreModule\Infrastructure\Http\ArgumentResolver\ApplicationContextResolver;
use ParkManager\Module\CoreModule\Infrastructure\Http\SectionsLoader;
use ParkManager\Module\CoreModule\Infrastructure\UserInterface\Web\Common\ApplicationContext;
use ParkManager\Module\CoreModule\Infrastructure\UserInterface\Web\EventListener\ApplicationSectionListener;

return function (ContainerConfigurator $c) {
    $di = $c->services()->defaults()
        ->autowire()
        ->autoconfigure()
        ->private()
        ->bind('$commandBus', ref('park_manager.command_bus'));

    $di->load('ParkManager\\Module\\CoreModule\\DataFixtures\\', __DIR__ . '/../../../DataFixtures');
};
