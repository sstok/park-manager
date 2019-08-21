<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ParkManager\Bundle\CoreBundle\Form\Type\DefaultMessageBusExtension;
use ParkManager\Bundle\CoreBundle\Form\Type\Security\ChangePasswordType;
use ParkManager\Bundle\CoreBundle\Form\Type\Security\SecurityUserHashedPasswordType;
use ParkManager\Bundle\CoreBundle\Form\Type\Security\SplitTokenType;

return static function (ContainerConfigurator $c) {
    $di = $c->services()->defaults()
        ->autowire()
        ->autoconfigure()
        ->private();

    $di->set(SplitTokenType::class);
    $di->set(SecurityUserHashedPasswordType::class);

    $di->set(ChangePasswordType::class);

    // Extension
    $di->set(DefaultMessageBusExtension::class);
};
