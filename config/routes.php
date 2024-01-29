<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Symfony\Component\Routing\Loader\Configurator;

return static function (RoutingConfigurator $routes) {
    $routes->import('../src/UI/Web/Action/', 'attribute');
    $routes->import('../src/UI/Web/Action/Admin', 'attribute')->prefix('admin/');
    $routes->import('../src/UI/Web/Action/Security', 'attribute');
};
