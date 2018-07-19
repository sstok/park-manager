<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\ORM\EntityManagerInterface;
use Hostnet\Component\FormHandler\HandlerFactory;
use Hostnet\Component\FormHandler\HandlerFactoryInterface;
use League\Tactician\Plugins\LockingMiddleware;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\MessageBusConfigurator;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\QueryBusConfigurator;
use ParkManager\Bundle\UserBundle\Action\{
    ChangePasswordAction,
    ConfirmPasswordResetAction,
    LoginAction,
    RequestPasswordResetAction
};
use ParkManager\Bundle\UserBundle\Form\FormHandlerRegistry;
use ParkManager\Bundle\UserBundle\Model\EventListener\UpdateAuthTokenWhenPasswordWasChanged;
use ParkManager\Bundle\UserBundle\ReadModel\DbalUserFinder;
use ParkManager\Bundle\UserBundle\Security\DbalUserProvider;
use ParkManager\Bundle\UserBundle\Security\FormAuthenticator;
use ParkManager\Bundle\UserBundle\Service\PasswordResetSwiftMailer;
use ParkManager\Component\ApplicationFoundation\Query\QueryBus;
use ParkManager\Component\Mailer\Sender;
use ParkManager\Component\SharedKernel\Event\EventEmitter;
use ParkManager\Component\User\Canonicalizer\SimpleEmailCanonicalizer;
use ParkManager\Component\User\Model\Handler\{
    ChangeUserPasswordHandler,
    ConfirmUserPasswordResetHandler,
    GetUserWithPasswordResetTokenHandler,
    RequestUserPasswordResetHandler
};
use ParkManager\Component\User\Model\UserCollection;
use ParkManager\Core\Application\Administrator\RegisterAdministratorHandler;
use ParkManager\Core\Infrastructure\Console\Command\RegisterAdministratorCommand;
use ParkManager\Core\Infrastructure\Doctrine\Administrator\DoctrineOrmAdministratorRepository;
use ParkManager\Core\Infrastructure\Security\AdministratorSecurityUser;

return function (ContainerConfigurator $c) {
    $di = $c->services()->defaults()
        ->public()
        ->autowire()
        // Bindings
        ->bind(QueryBus::class, ref('park_manager.query_bus.administrator'))
        ->bind(EventEmitter::class, ref('park_manager.command_bus.administrator.domain_event_emitter'))
        ->bind(UserCollection::class, ref('park_manager.repository.administrator'))
        ->bind(EntityManagerInterface::class, ref('doctrine.orm.entity_manager'))
        ->bind(HandlerFactoryInterface::class, ref('park_manager.form_handler.administrator.handler_factory'))
        // --
        ->bind('$loginRoute', 'park_manager.administrator.security_login')
        ->bind('$emailCanonicalizer', ref(SimpleEmailCanonicalizer::class))
        ->bind('$sender', ref(Sender::class));

    MessageBusConfigurator::register($di, 'park_manager.command_bus.administrator')
        ->middlewares()
            ->register(LockingMiddleware::class)
            ->doctrineOrmTransaction('default')
            ->domainEvents()
                ->subscriber(UpdateAuthTokenWhenPasswordWasChanged::class, [ref('park_manager.security.user_provider.administrator')])
            ->end()
        ->end()
        ->handlers()
            ->register(RegisterAdministratorHandler::class)
            ->register(ChangeUserPasswordHandler::class)
            ->register(RequestUserPasswordResetHandler::class, ['$passwordResetMailer' => ref('park_manager.mailer.administrator.password_reset')])
            ->register(ConfirmUserPasswordResetHandler::class)
        ->end();

    QueryBusConfigurator::register($di, 'park_manager.query_bus.administrator')
        ->handlers()
            ->register(GetUserWithPasswordResetTokenHandler::class, [ref('park_manager.read_model.administrator_finder')])
        ->end();

    $di->set(SimpleEmailCanonicalizer::class)->private();

    $di->set('park_manager.repository.administrator', DoctrineOrmAdministratorRepository::class);
    $di->set('park_manager.read_model.administrator_finder', DbalUserFinder::class)
        ->private()
        ->args([ref('doctrine.dbal.default_connection'), 'public.administrator']);

    // Services
    $di->set('park_manager.mailer.administrator.password_reset', PasswordResetSwiftMailer::class)
        ->autowire()
        ->arg('$confirmResetRoute', 'park_manager.administrator.confirm_password_reset');

    // Form
    $di->set('park_manager.form_handler.administrator.handler_registry', FormHandlerRegistry::class)->arg(0, [])->private();
    $di->set('park_manager.form_handler.administrator.handler_factory', HandlerFactory::class)
        ->private()
        ->args([ref('form.factory'), ref('park_manager.form_handler.administrator.handler_registry')]);

    // FormHandlers
    $formHandlers = $c->services();

    $formHandlers->set('park_manager.form_handler.security.administrator.request_password_reset')
        ->parent('park_manager.form_handler.security.request_password_reset')
        ->tag('admin_form.handler')
        ->args([ref('park_manager.command_bus.administrator'), 'park_manager.administrator.security_login']);

    $formHandlers->set('park_manager.form_handler.security.administrator.confirm_password_reset')
        ->parent('park_manager.form_handler.security.confirm_password_reset')
        ->tag('admin_form.handler')
        ->args([ref('park_manager.command_bus.administrator'), 'park_manager.administrator.security_login']);

    $formHandlers->set('park_manager.form_handler.security.administrator.change_password')
        ->parent('park_manager.form_handler.security.change_password')
        ->tag('admin_form.handler')
        ->args([ref('park_manager.command_bus.administrator'), 'admin_home']);

    // Actions
    $di->set('park_manager.web_action.security.administrator.login', LoginAction::class)
        ->arg('$template', '@ParkManagerCore/security/login.html.twig');
    $di->set('park_manager.web_action.security.administrator.request_password_reset', RequestPasswordResetAction::class)
        ->arg('$template', '@ParkManagerCore/security/password_reset.html.twig');
    $di->set('park_manager.web_action.security.administrator.confirm_password_reset', ConfirmPasswordResetAction::class)
        ->arg('$template', '@ParkManagerCore/security/password_reset_confirm.html.twig');
    $di->set('park_manager.web_action.security.administrator.change_password', ChangePasswordAction::class)
        ->arg('$template', '@ParkManagerCore/security/change_password.html.twig');

    // Security
    $di
        ->private()
        ->autowire(true);

    $di->set('park_manager.security.user_provider.administrator', DbalUserProvider::class)
        ->args([
            ref('doctrine.dbal.default_connection'),
            ref(SimpleEmailCanonicalizer::class),
            'public.administrator',
            AdministratorSecurityUser::class,
        ])
        ->autoconfigure(false);

    $di->set('park_manager.security.guard.form.administrator', FormAuthenticator::class)
        ->arg('$loginRoute', 'park_manager.administrator.security_login')
        ->arg('$defaultSuccessRoute', 'admin_home');

    // CliCommands
    $di->set(RegisterAdministratorCommand::class)
        ->arg('$commandBus', ref('park_manager.command_bus.administrator'))
        ->tag('console.command', ['command' => 'park-manager:administrator:register']);
};
