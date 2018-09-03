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
use ParkManager\Module\WebhostingModule\Domain\DomainName\WebhostingDomainNameRepository;
use ParkManager\Module\WebhostingModule\Infrastructure\Doctrine\DomainName\WebhostingDomainNameOrmRepository;

return function (ContainerConfigurator $c) {
    $di = $c->services()->defaults()
        ->autowire()
        ->autoconfigure()
        ->private()
        // Bindings
        ->bind(EntityManagerInterface::class, ref('doctrine.orm.entity_manager'));

    $di->set(WebhostingDomainNameOrmRepository::class)
        ->alias(WebhostingDomainNameRepository::class, WebhostingDomainNameOrmRepository::class);
};
