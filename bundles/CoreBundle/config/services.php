<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ParkManager\Bundle\CoreBundle\ArgumentResolver\FormFactoryResolver;
use ParkManager\Bundle\CoreBundle\DependencyInjection\AutoServiceConfigurator;
use ParkManager\Bundle\CoreBundle\Doctrine\Administrator\DoctrineOrmAdministratorRepository;
use ParkManager\Bundle\CoreBundle\Doctrine\Client\DoctrineOrmClientRepository;
use ParkManager\Bundle\CoreBundle\Routing\SectionsLoader;
use Rollerworks\Component\SplitToken\Argon2SplitTokenFactory;

return static function (ContainerConfigurator $c): void {
    $di = $c->services()->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
    ;

    $autoDi = new AutoServiceConfigurator($di);

    $di->load('ParkManager\\Bundle\\CoreBundle\\', __DIR__ . '/../src/*')
        ->exclude([__DIR__ . '/../src/{DependencyInjection,Model,Test,Http,UseCase,DataFixtures}'])
    ;

    $autoDi->set(Argon2SplitTokenFactory::class);
    $autoDi->set('park_manager.repository.administrator', DoctrineOrmAdministratorRepository::class);
    $autoDi->set('park_manager.repository.client_user', DoctrineOrmClientRepository::class);

    $di->set(SectionsLoader::class)
        ->tag('routing.loader')
        ->arg('$loader', ref('routing.resolver'))
    ;

    $di->set(FormFactoryResolver::class)
        ->tag('controller.argument_value_resolver', ['priority' => 30])
    ;

    $di->load('ParkManager\\Bundle\\CoreBundle\\UseCase\\', __DIR__ . '/../src/UseCase/**/*Handler.php')
        ->tag('messenger.message_handler', ['bus' => 'park_manager.command_bus'])
    ;
};
