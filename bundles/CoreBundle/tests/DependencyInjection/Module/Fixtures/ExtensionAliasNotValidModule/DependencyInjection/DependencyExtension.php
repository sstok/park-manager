<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Tests\DependencyInjection\Module\Fixtures\ExtensionAliasNotValidModule\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class DependencyExtension extends Extension
{
    public function getAlias(): string
    {
        return 'extension_valid_is_not';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
    }
}
