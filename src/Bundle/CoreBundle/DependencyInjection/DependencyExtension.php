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
use ParkManager\Bridge\Doctrine\Type\DateTimeImmutableType;
use ParkManager\Component\Core\Model\Command\RegisterAdministrator;
use Rollerworks\Bundle\AppSectioningBundle\DependencyInjection\SectioningFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
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
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        $this->registerApplicationSections($container, $config);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDoctrineConfig($container);
    }

    private function prependDoctrineConfig(ContainerBuilder $container): void
    {
        $types = ['array_collection' => ['class' => ArrayCollectionType::class, 'commented' => true]];
        if (!class_exists('Doctrine\DBAL\Types\DateTimeImmutableType')) {
            $types['datetime_immutable'] = ['class' => DateTimeImmutableType::class, 'commented' => true];
        }

        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => $types,
            ],
        ]);
    }

    private function prependProophConfig(ContainerBuilder $container): void
    private function registerApplicationSections(ContainerBuilder $container, $config): void
    {
        $factory = new SectioningFactory($container, 'park_manager.section');

        foreach ($config['sections'] as $section => $sectionConfig) {
            $factory->set($section, $sectionConfig);
        }
    }
}
