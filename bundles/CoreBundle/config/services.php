<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ParkManager\Bundle\CoreBundle\Context\ApplicationContext;
use ParkManager\Bundle\CoreBundle\DependencyInjection\AutoServiceConfigurator;
use ParkManager\Bundle\CoreBundle\Doctrine\Administrator\DoctrineOrmAdministratorRepository;
use ParkManager\Bundle\CoreBundle\Doctrine\Client\DoctrineOrmClientRepository;
use ParkManager\Bundle\CoreBundle\Doctrine\DoctrineDbalAuthenticationFinder;
use ParkManager\Bundle\CoreBundle\EventListener\ApplicationSectionListener;
use ParkManager\Bundle\CoreBundle\ArgumentResolver\ApplicationContextResolver;
use ParkManager\Bundle\CoreBundle\ArgumentResolver\FormFactoryResolver;
use ParkManager\Bundle\CoreBundle\Routing\SectionsLoader;
use Rollerworks\Component\SplitToken\Argon2SplitTokenFactory;
use Rollerworks\Component\SplitToken\SplitTokenFactory;

return static function (ContainerConfigurator $c) {
    $di = $c->services()->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
        ->bind('$eventBus', ref('park_manager.event_bus'));

    $autoDi = new AutoServiceConfigurator($di);

    $di->load('ParkManager\\Bundle\\CoreBundle\\', __DIR__ . '/../src/*')
        ->exclude([__DIR__ . '/../src/{DependencyInjection,Entity,Test,Http,UseCase,DataFixtures}']);

    $autoDi->set(Argon2SplitTokenFactory::class);
    $autoDi->set('park_manager.repository.administrator', DoctrineOrmAdministratorRepository::class);
    $autoDi->set('park_manager.repository.client_user', DoctrineOrmClientRepository::class);

    // Authentication finders
    $di->set('park_manager.query_finder.administrator', DoctrineDbalAuthenticationFinder::class)
        ->arg('$table', 'administrator');
    $di->set('park_manager.query_finder.client', DoctrineDbalAuthenticationFinder::class)
        ->arg('$table', 'client');

    // RoutingLoader
    $di->set(SectionsLoader::class)
        ->tag('routing.loader')
        ->arg('$loader', ref('routing.resolver'))
        ->arg('$primaryHost', '%park_manager.config.primary_host%')
        ->arg('$isSecure', '%park_manager.config.is_secure%');

    $autoDi->set('park_manager.application_context', ApplicationContext::class);

    $di->set(ApplicationSectionListener::class)
        ->tag('kernel.event_subscriber')
        ->tag('kernel.reset', ['method' => 'reset'])
        ->arg('$sectionMatchers', [
            'admin' => ref('park_manager.section.admin.request_matcher'),
            'private' => ref('park_manager.section.private.request_matcher'),
            'client' => ref('park_manager.section.client.request_matcher'),
        ]);

    $di->set(FormFactoryResolver::class)
        ->tag('controller.argument_value_resolver', ['priority' => 30]);

    $di->set(ApplicationContextResolver::class)
        ->tag('controller.argument_value_resolver', ['priority' => 30]);

    $di->load('ParkManager\\Bundle\\CoreBundle\\UseCase\\', __DIR__ . '/../src/UseCase/**/*Handler.php')
        ->tag('messenger.message_handler', ['bus' => 'park_manager.command_bus']);
};
