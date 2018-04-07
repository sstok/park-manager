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

namespace ParkManager\Module\Webhosting\Infrastructure\DependencyInjection;

use ParkManager\Component\Module\ParkManagerModuleDependencyExtension;
use ParkManager\Component\Module\Traits\DoctrineDbalTypesConfiguratorTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class DependencyExtension extends ParkManagerModuleDependencyExtension
{
    use DoctrineDbalTypesConfiguratorTrait;

    public const EXTENSION_ALIAS = 'park_manager_webhosting';

    protected function loadModule(array $configs, ContainerBuilder $container, LoaderInterface $loader): void
    {
        $loader->load('*.php', 'glob');
    }

    public function getAlias(): string
    {
        return self::EXTENSION_ALIAS;
    }

    public function getModuleName(): string
    {
        return 'ParkManagerWebhosting';
    }
}
