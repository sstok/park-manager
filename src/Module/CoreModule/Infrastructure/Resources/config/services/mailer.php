<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ParkManager\Module\CoreModule\Infrastructure\DependencyInjection\AutoServiceConfigurator;
use ParkManager\Module\CoreModule\Infrastructure\Mailer\Client\EmailAddressChangeRequestMailerImp;
use ParkManager\Module\CoreModule\Infrastructure\Mailer\Client\PasswordResetMailerImpl;

return function (ContainerConfigurator $c) {
    $di = $c->services()->defaults()
        ->autowire()
        ->autoconfigure(false)
        ->private();

    $autoDi = new AutoServiceConfigurator($di);

    // Client
    $autoDi->set(EmailAddressChangeRequestMailerImp::class);
    $autoDi->set(PasswordResetMailerImpl::class);
};
