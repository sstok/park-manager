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

namespace ParkManager\Core\Infrastructure\DependencyInjection;

use ParkManager\Bridge\Doctrine\Type\ArrayCollectionType;
use ParkManager\Core\Infrastructure\DependencyInjection\Module\ParkManagerModuleDependencyExtension;
use Rollerworks\Bundle\RouteAutowiringBundle\RouteImporter;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class DependencyExtension extends ParkManagerModuleDependencyExtension
{
    public const EXTENSION_ALIAS = 'park_manager';

    public function getAlias(): string
    {
        return self::EXTENSION_ALIAS;
    }

    public function getModuleName(): string
    {
        return 'ParkManagerCore';
    }

    protected function loadModule(array $configs, ContainerBuilder $container, LoaderInterface $loader): void
    {
        $loader->load('*.php', 'glob');
    }

    public function prependExtra(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => [
                    'array_collection' => ['class' => ArrayCollectionType::class, 'commented' => true],
                ],
            ],
        ]);
    }

    protected function registerRoutes(RouteImporter $routeImporter, ?string $configDir): void
    {
        $routeImporter->import($configDir.'/routing/administrator.yaml', 'park_manager.admin_section.root');
    }
}
