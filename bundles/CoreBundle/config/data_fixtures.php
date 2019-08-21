<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ParkManager\Bundle\CoreBundle\Doctrine\Shared\DoctrineDbalAuthenticationFinder;
use ParkManager\Bundle\CoreBundle\Http\ArgumentResolver\FormFactoryResolver;
use Rollerworks\Component\SplitToken\Argon2SplitTokenFactory;
use ParkManager\Bundle\CoreBundle\DependencyInjection\AutoServiceConfigurator;
use ParkManager\Bundle\CoreBundle\Doctrine\Administrator\DoctrineOrmAdministratorRepository;
use ParkManager\Bundle\CoreBundle\Doctrine\Client\DoctrineOrmClientRepository;
use ParkManager\Bundle\CoreBundle\Http\ArgumentResolver\ApplicationContextResolver;
use ParkManager\Bundle\CoreBundle\Http\SectionsLoader;
use ParkManager\Bundle\CoreBundle\Common\ApplicationContext;
use ParkManager\Bundle\CoreBundle\EventListener\ApplicationSectionListener;

return function (ContainerConfigurator $c) {
    $di = $c->services()->defaults()
        ->autowire()
        ->autoconfigure()
        ->private()
        ->bind('$commandBus', ref('park_manager.command_bus'));

    $di->load('ParkManager\\Bundle\\CoreBundle\\DataFixtures\\', __DIR__ . '/../src/DataFixtures');
};
