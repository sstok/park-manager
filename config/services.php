<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\Persistence\ObjectManager;
use ParkManager\Application\Service\TLS\CertificateFactoryImpl;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\User\UserRepository;
use ParkManager\Domain\Webhosting\DomainName\WebhostingDomainNameId;
use ParkManager\Domain\Webhosting\DomainName\WebhostingDomainNameRepository;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceRepository;
use ParkManager\Infrastructure\Doctrine\ConstraintsTypeConfigurator;
use ParkManager\Infrastructure\Doctrine\Repository\WebhostingSpaceOrmRepository;
use ParkManager\Infrastructure\Doctrine\Repository\SharedConstraintSetOrmRepository;
use ParkManager\Infrastructure\Security\Guard\FormAuthenticator;
use ParkManager\Infrastructure\Security\PermissionExpressionProvider;
use ParkManager\Infrastructure\Security\UserProvider;
use ParkManager\Infrastructure\Webhosting\Constraint\ConstraintsFactory;
use ParkManager\UI\Web\ArgumentResolver\ModelResolver;
use ParkManager\UI\Web\ArgumentResolver\SplitTokenResolver;
use Rollerworks\Component\SplitToken\Argon2SplitTokenFactory;
use Rollerworks\Component\SplitToken\SplitTokenFactory;

return static function (ContainerConfigurator $c): void {
    $di = $c->services()->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
        ->bind('$commandBus', ref('park_manager.command_bus'))
        ->bind(ObjectManager::class, ref('doctrine.orm.default_entity_manager'));

    // Note: Repositories are loaded separate as autowiring the entire Domain is not
    // possible. Entities and other models must not be registered as services.
    //
    // But registering the repositories separate breaks their singlyImplemented
    // interface aliasing.
    $di->load('ParkManager\\', __DIR__ . '/../src/**/*Repository.php');

    // Register the handler, not the actual commands.
    $di->load('ParkManager\\Application\\Command\\', __DIR__ . '/../src/Application/Command/**/*Handler.php')
        ->tag('messenger.message_handler', ['bus' => 'park_manager.command_bus']);

    $di->load('ParkManager\\', __DIR__ . '/../src/*')
        ->exclude([
            __DIR__ . '/../src/Kernel.php',
            __DIR__ . '/../src/**/*Event.php',
            __DIR__ . '/../src/{Domain,DataFixtures}',
            __DIR__ . '/../src/Application/{Command,Event}',
            __DIR__ . '/../src/Application/Service/TLS/Violation',
            __DIR__ . '/../src/Infrastructure/Doctrine',
            __DIR__ . '/../src/Infrastructure/Security/*User.php',
            __DIR__ . '/../src/Infrastructure/Security/Permission',
            __DIR__ . '/../src/UI/Web/Form/{ConfirmationHandler,DataTransformer,DataMapper}',
            __DIR__ . '/../src/UI/Web/Response',
        ]);

    $di->get(CertificateFactoryImpl::class)
        ->args(['%env(base64:TLS_STORAGE_PUBKEY)%']);

    $di->load('ParkManager\\Infrastructure\\Security\\Permission\\', __DIR__ . '/../src/Infrastructure/Security/Permission/**/*Decider.php')
        ->tag('park_manager.security.permission_decider');

    $di->get(SplitTokenResolver::class)
        ->tag('controller.argument_value_resolver', ['priority' => 255])
        ->autoconfigure(false);

    $di->get(ModelResolver::class)
        ->tag('controller.argument_value_resolver', ['priority' => 255])
        ->autoconfigure(false)
        ->args([
            service_locator([
                User::class => ref(UserRepository::class),
                Space::class => ref(WebhostingSpaceRepository::class),
                WebhostingDomainNameId::class => ref(WebhostingDomainNameRepository::class),
            ]),
            [
                UserId::class => 'fromString',
                WebhostingDomainNameId::class => 'fromString',
            ],
        ]);

    $di->set(Argon2SplitTokenFactory::class);
    $di->alias(SplitTokenFactory::class, Argon2SplitTokenFactory::class);

    $di->load('ParkManager\\UI\\Console\\', __DIR__ . '/../src/UI/Console/**/*Command.php')
        ->tag('console.command');

    // -- Webhosting
    $di->set(ConstraintsFactory::class)->arg(0, []);
    $di->set(ConstraintsTypeConfigurator::class);
    $di->get(SharedConstraintSetOrmRepository::class)->configurator(ref(ConstraintsTypeConfigurator::class));
    $di->get(WebhostingSpaceOrmRepository::class)->configurator(ref(ConstraintsTypeConfigurator::class));

    // -- Security
    $di->set('park_manager.security.user_provider', UserProvider::class);
    $di->set('park_manager.security.guard.form', FormAuthenticator::class);

    $di->set(PermissionExpressionProvider::class)
        ->tag('security.expression_language_provider')
        ->autoconfigure(false);
};
