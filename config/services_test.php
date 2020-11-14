<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ParkManager\Application\Service\CurrentStorageUsageRetriever;
use ParkManager\Application\Service\TLS\CertificateFactoryImpl;
use ParkManager\Tests\Mock\Application\Service\CurrentStorageUsageRetrieverMock;

return static function (ContainerConfigurator $c): void {
    $c->import('services_dev.php');

    $di = $c->services();
    $di->get(CertificateFactoryImpl::class)
        ->public();

    $di->set(CurrentStorageUsageRetrieverMock::class);
    $di->alias(CurrentStorageUsageRetriever::class, CurrentStorageUsageRetrieverMock::class);
};
