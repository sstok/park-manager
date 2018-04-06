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

use ParkManager\Component\Mailer\NullSender;
use ParkManager\Component\Mailer\Sender;
use ParkManager\Component\Model\LogMessage\LogMessages;

return function (ContainerConfigurator $c) {
    $di = $c->services();

    // ServiceBus LogMessages allow the service-bus to communicate non-critical messages
    // back to higher layers.
    $di->set('park_manager.service_bus.log_messages', LogMessages::class)
        ->alias(LogMessages::class, 'park_manager.service_bus.log_messages');

    $di->set(NullSender::class)->alias(Sender::class, NullSender::class);
};
