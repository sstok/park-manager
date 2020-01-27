<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ParkManager\Domain\Administrator\AdministratorRepository;
use ParkManager\Domain\User\UserRepository;
use ParkManager\Infrastructure\Doctrine\ConstraintsTypeConfigurator;
use ParkManager\Infrastructure\Doctrine\Repository\WebhostingSpaceOrmRepository;
use ParkManager\Infrastructure\Doctrine\Repository\WebhostingPlanOrmRepository;
use ParkManager\Infrastructure\Security\AdministratorUser;
use ParkManager\Infrastructure\Security\User;
use ParkManager\Infrastructure\Security\Guard\FormAuthenticator;
use ParkManager\Infrastructure\Security\UserProvider;
use ParkManager\Infrastructure\Webhosting\Constraint\ConstraintsFactory;
use Rollerworks\Component\SplitToken\Argon2SplitTokenFactory;
use Rollerworks\Component\SplitToken\SplitTokenFactory;

return static function (ContainerConfigurator $c): void {
    $di = $c->services()->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
        ->bind('$commandBus', ref('park_manager.command_bus'));

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
            __DIR__ . '/../src/Infrastructure/Doctrine',
            __DIR__ . '/../src/Infrastructure/Security/*User.php',
            __DIR__ . '/../src/UI/Web/Form/{ConfirmationHandler,DataTransformer,DataMapper}',
            __DIR__ . '/../src/UI/Web/Response',
        ]);

    $di->set(Argon2SplitTokenFactory::class);
    $di->alias(SplitTokenFactory::class, Argon2SplitTokenFactory::class);

    $di->load('ParkManager\\UI\\Console\\', __DIR__ . '/../src/UI/Console/**/*Command.php')
        ->tag('console.command');

    $di->set('park_manager.security.user_provider.administrator', UserProvider::class)
        ->args([ref(AdministratorRepository::class), AdministratorUser::class]);

    $di->set('park_manager.security.user_provider.user_user', UserProvider::class)
        ->args([ref(UserRepository::class), User::class]);

    // -- Webhosting
    $di->set(ConstraintsFactory::class)->arg(0, []);
    $di->set(ConstraintsTypeConfigurator::class);
    $di->get(WebhostingPlanOrmRepository::class)->configurator(ref(ConstraintsTypeConfigurator::class));
    $di->get(WebhostingSpaceOrmRepository::class)->configurator(ref(ConstraintsTypeConfigurator::class));

    // -- Security
    $di->set('park_manager.security.guard.form.administrator', FormAuthenticator::class)
        ->arg('$loginRoute', 'park_manager.admin.security_login')
        ->arg('$defaultSuccessRoute', 'park_manager.admin.home');

    $di->set('park_manager.security.guard.form.user', FormAuthenticator::class)
        ->arg('$loginRoute', 'park_manager.user.security_login')
        ->arg('$defaultSuccessRoute', 'park_manager.user.home');
};
