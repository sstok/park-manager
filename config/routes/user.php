<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Symfony\Component\Routing\Loader\Configurator;

use ParkManager\UI\Web\Action\Client\ConfirmPasswordResetAction;
use ParkManager\UI\Web\Action\Client\RequestPasswordResetAction;
use ParkManager\UI\Web\Action\Client\SecurityLoginAction;
use ParkManager\UI\Web\Action\HomepageAction;
use ParkManager\UI\Web\Action\SecurityLogoutAction;

return static function (RoutingConfigurator $routes) {
    $routes->import('../../src/UI/Web/Action/Client', 'annotation');

    $routes->add('park_manager.admin.security_logout', '/logout')
        ->controller(SecurityLogoutAction::class)
        ->methods(['GET']);

    $routes->add('park_manager.client.home', '/')->controller(HomepageAction::class);
};
