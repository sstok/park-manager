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

namespace ParkManager\Module\WebhostingModule\Infrastructure\DependencyInjection;

use ParkManager\Component\Module\ParkManagerModuleDependencyExtension;
use ParkManager\Component\Module\Traits\DoctrineDbalTypesConfiguratorTrait;
use ParkManager\Module\WebhostingModule\Application\Service\Package\PackageConfigurationApplier;
use ParkManager\Module\WebhostingModule\Domain\Package\Capability;
use ParkManager\Module\WebhostingModule\Infrastructure\Service\Package\CapabilityGuard;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class DependencyExtension extends ParkManagerModuleDependencyExtension
{
    use DoctrineDbalTypesConfiguratorTrait;

    public const EXTENSION_ALIAS = 'park_manager_webhosting';

    protected function loadModule(array $configs, ContainerBuilder $container, LoaderInterface $loader): void
    {
        $loader->load('*.php', 'glob');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->registerForAutoconfiguration(Capability::class)
            ->addTag('park_manager.webhosting_capability');
        $container->registerForAutoconfiguration(CapabilityGuard::class)
            ->addTag('park_manager.webhosting_capability_guard');
        $container->registerForAutoconfiguration(PackageConfigurationApplier::class)
            ->addTag('park_manager.webhosting_capability_config_applier');

        $this->processCapabilitiesMapping($container, $config);
    }

    public function getAlias(): string
    {
        return self::EXTENSION_ALIAS;
    }

    public function getModuleName(): string
    {
        return 'ParkManagerWebhosting';
    }

    private function processCapabilitiesMapping(ContainerBuilder $container, array $config): void
    {
        $enabled = $container->getParameterBag()->resolveValue($config['capabilities']);
        $container->setParameter('park_manager.webhosting.package_capabilities.enabled', $enabled);
        $container->setParameter('park_manager.webhosting.package_capabilities.command_mapping', []);

        if ($enabled === false) {
            return;
        }

        $commandToCapabilityMapping = [];
        foreach ($config['capabilities']['mapping'] as $command => $mappingConfig) {
            $commandToCapabilityMapping[$command] = [
                'capability' => $mappingConfig['capability'],
                'mapping' => $mappingConfig['mapping'],
            ];
        }

        $container->setParameter(
            'park_manager.webhosting.package_capabilities.command_mapping',
            $commandToCapabilityMapping
        );
    }
}
