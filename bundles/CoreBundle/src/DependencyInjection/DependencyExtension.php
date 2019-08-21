<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\DependencyInjection;

use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use ParkManager\Bundle\CoreBundle\DependencyInjection\Traits\DoctrineDbalTypesConfiguratorTrait;
use ParkManager\Bundle\CoreBundle\DependencyInjection\Traits\ExtensionPathResolver;
use ParkManager\Bundle\CoreBundle\DependencyInjection\Traits\RoutesImporterTrait;
use ParkManager\Bundle\CoreBundle\DependencyInjection\Traits\ServiceLoaderTrait;
use ParkManager\Bundle\CoreBundle\Twig\AppContextGlobal;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use function class_exists;

final class DependencyExtension extends Extension implements PrependExtensionInterface
{
    use ExtensionPathResolver;
    use ServiceLoaderTrait;
    use DoctrineDbalTypesConfiguratorTrait;
    use RoutesImporterTrait;

    public const EXTENSION_ALIAS = 'park_manager_core';

    public function getAlias(): string
    {
        return self::EXTENSION_ALIAS;
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $this->initBundlePath();

        $loader = $this->getServiceLoader($container, $this->bundlePath . '/config');
        $loader->load('services.php');
        $loader->load('services/*.php', 'glob');

        if (class_exists(DoctrineFixturesBundle::class)) {
            $loader->load('data_fixtures.php');
        }

        $routeImporter = $this->getRouteImporter($container);
        $routeImporter->import($this->bundlePath . '/config/routing/administrator.php', 'park_manager.admin_section.root');
        $routeImporter->import($this->bundlePath . '/config/routing/client.php', 'park_manager.client_section.root');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->initBundlePath();
        $this->registerDoctrineDbalTypes($container, $this->bundlePath . '/src');

        $container->prependExtensionConfig('twig', [
            'globals' => ['app_context' => '@' . AppContextGlobal::class],
        ]);
    }
}
