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

use ParkManager\Module\CoreModule\Infrastructure\Security\AdministratorUser;
use ParkManager\Module\CoreModule\Infrastructure\Security\FormAuthenticator;
use ParkManager\Module\CoreModule\Infrastructure\Security\GenericUser;
use ParkManager\Module\CoreModule\Infrastructure\Security\UserProvider;

return function (ContainerConfigurator $c) {
    $di = $c->services()->defaults()
        ->autowire()
        ->autoconfigure(false)
        ->private();

    $di->set('park_manager.security.user_provider.administrator', UserProvider::class)
        ->args([ref('park_manager.repository.administrator'), AdministratorUser::class]);

    $di->set('park_manager.security.user_provider.generic_user', UserProvider::class)
        ->args([ref('park_manager.repository.generic_user'), GenericUser::class]);

    $di->set('park_manager.security.guard.form.administrator', FormAuthenticator::class)
        ->arg('$loginRoute', 'park_manager.admin.security_login')
        ->arg('$defaultSuccessRoute', 'park_manager.admin.home');

    $di->set('park_manager.security.guard.form.client', FormAuthenticator::class)
        ->arg('$loginRoute', 'park_manager.client.security_login')
        ->arg('$defaultSuccessRoute', 'home');
};