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

namespace ParkManager\Bundle\CoreBundle\DependencyInjection;

use ParkManager\Bridge\Doctrine\Type\ArrayCollectionType;
use ParkManager\Bundle\CoreBundle\Doctrine\Type\AdministratorIdType;
use Rollerworks\Bundle\RouteAutowiringBundle\RouteImporter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class DependencyExtension extends Extension implements PrependExtensionInterface
{
    public const EXTENSION_ALIAS = 'park_manager';

    public function getAlias(): string
    {
        return self::EXTENSION_ALIAS;
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        //$config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        $this->registerRoutes($container);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration();
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDoctrineConfig($container);

        $container->prependExtensionConfig('twig', [
            'paths' => [realpath(dirname(__DIR__).'/templates') => 'ParkManager'],
        ]);
    }

    private function prependDoctrineConfig(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => [
                    'array_collection' => ['class' => ArrayCollectionType::class, 'commented' => true],
                    AdministratorIdType::NAME => ['class' => AdministratorIdType::class, 'commented' => true],
                ],
            ],
        ]);
    }

    private function registerRoutes(ContainerBuilder $container): void
    {
        $routeImporter = new RouteImporter($container);
        $routeImporter->addObjectResource($this);
        $routeImporter->import('@ParkManagerCoreBundle/Resources/config/routing/administrator.yaml', 'park_manager.admin_section.root');
    }
}
