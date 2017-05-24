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

namespace ParkManager\Common\Infrastructure\Symfony\DependencyInjection;

use Rollerworks\Bundle\AppSectioningBundle\DependencyInjection\SectioningFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class DependencyExtension extends Extension
{
    public const EXTENSION_ALIAS = 'park_manager';

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $this->registerApplicationSections($container, $config);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/services/'));
        $loader->load('core.xml');
    }

    public function getAlias(): string
    {
        return self::EXTENSION_ALIAS;
    }

    private function registerApplicationSections(ContainerBuilder $container, $config): void
    {
        $factory = new SectioningFactory($container, 'park_manager.section');

        foreach ($config['sections'] as $section => $sectionConfig) {
            $factory->set($section, $sectionConfig);
        }
    }
}
