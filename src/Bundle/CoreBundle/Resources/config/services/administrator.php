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
use ParkManager\Bundle\CoreBundle\Model\DoctrineOrmAdministratorRepository;
use ParkManager\Bundle\CoreBundle\Security\AdministratorSecurityUser;
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
use ParkManager\Component\Core\Model\Handler\RegisterAdministratorHandler;
use ParkManager\Component\User\Canonicalizer\SimpleEmailCanonicalizer;
use ParkManager\Component\User\Model\Handler\{
    ChangeUserPasswordHandler,
    ConfirmUserPasswordResetHandler,
    GetUserWithPasswordResetTokenHandler,
    RequestUserPasswordResetHandler
};
use ParkManager\Component\User\Model\UserCollection;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\QueryBus;

return function (ContainerConfigurator $c) {
    $di = $c->services()->defaults()
        ->public()
        ->autowire(true)
        // Bindings
        ->bind(QueryBus::class, ref('prooph_service_bus.administrator.query_bus'))
        ->bind(EventBus::class, ref('prooph_service_bus.administrator.event_bus'))
        ->bind(UserCollection::class, ref('park_manager.repository.administrator'))
        ->bind(EntityManagerInterface::class, ref('doctrine.orm.entity_manager'))
        ->bind(HandlerFactoryInterface::class, ref('park_manager.form_handler.administrator.handler_factory'))
        // --
        ->bind('$loginRoute', 'park_manager.administrator.security_login')
        ->bind('$emailCanonicalizer', ref(SimpleEmailCanonicalizer::class))
        ->bind('$sender', ref('sylius.email_sender'));

    $di->set(SimpleEmailCanonicalizer::class)->private();

    $di->set('park_manager.repository.administrator', DoctrineOrmAdministratorRepository::class);
    $di->set('park_manager.read_model.administrator_finder', DbalUserFinder::class)
        ->private()
        ->args([ref('doctrine.dbal.default_connection'), 'public.administrator']);

    // CommandHandler
    $di->set('park_manager.command_handler.register_administrator', RegisterAdministratorHandler::class);
    $di->set('park_manager.command_handler.change_administrator_password', ChangeUserPasswordHandler::class);
    $di->set('park_manager.command_handler.request_administrator_password_reset', RequestUserPasswordResetHandler::class)
        ->arg('$passwordResetMailer', ref('park_manager.mailer.administrator.password_reset'));
    $di->set('park_manager.command_handler.confirm_administrator_password_reset', ConfirmUserPasswordResetHandler::class);

    // QueryHandler
    $di->set('park_manager.query_handler.get_administrator_by_password_reset_token', GetUserWithPasswordResetTokenHandler::class)
        ->args([ref('park_manager.read_model.administrator_finder')]);

    // EventListener
    $di->set('park_manager.domain_event_listener.update_auth_token_when_password_was_changed.administrator', UpdateAuthTokenWhenPasswordWasChanged::class)
        ->args([ref('park_manager.security.user_provider.administrator'), ref('security.token_storage')]);

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
        ->args([ref('prooph_service_bus.administrator.command_bus'), 'park_manager.administrator.security_login']);

    $formHandlers->set('park_manager.form_handler.security.administrator.confirm_password_reset')
        ->parent('park_manager.form_handler.security.confirm_password_reset')
        ->tag('admin_form.handler')
        ->args([ref('prooph_service_bus.administrator.command_bus'), 'park_manager.administrator.security_login']);

    $formHandlers->set('park_manager.form_handler.security.administrator.change_password')
        ->parent('park_manager.form_handler.security.change_password')
        ->tag('admin_form.handler')
        ->args([ref('prooph_service_bus.administrator.command_bus'), 'admin_home']);

    // Actions
    $di->set('park_manager.web_action.security.administrator.login', LoginAction::class)
        ->arg('$template', '@ParkManager/web/security/login.html.twig');
    $di->set('park_manager.web_action.security.administrator.request_password_reset', RequestPasswordResetAction::class)
        ->arg('$template', '@ParkManager/web/security/password_reset.html.twig');
    $di->set('park_manager.web_action.security.administrator.confirm_password_reset', ConfirmPasswordResetAction::class)
        ->arg('$template', '@ParkManager/web/security/password_reset_confirm.html.twig');
    $di->set('park_manager.web_action.security.administrator.change_password', ChangePasswordAction::class)
        ->arg('$template', '@ParkManager/web/security/change_password.html.twig');

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
};
