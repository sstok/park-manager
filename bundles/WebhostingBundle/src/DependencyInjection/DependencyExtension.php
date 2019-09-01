<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\DependencyInjection;

use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use ParkManager\Bundle\CoreBundle\DependencyInjection\Traits\DoctrineDbalTypesConfiguratorTrait;
use ParkManager\Bundle\CoreBundle\DependencyInjection\Traits\ExtensionPathResolver;
use ParkManager\Bundle\CoreBundle\DependencyInjection\Traits\RoutesImporterTrait;
use ParkManager\Bundle\CoreBundle\DependencyInjection\Traits\ServiceLoaderTrait;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Capability;
use ParkManager\Bundle\WebhostingBundle\Plan\CapabilityConfigurationApplier;
use ParkManager\Bundle\WebhostingBundle\Plan\CapabilityGuard;
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

    public const EXTENSION_ALIAS = 'park_manager_webhosting';

    public function load(array $configs, ContainerBuilder $container): void
    {
        $this->initBundlePath();

        $loader = $this->getServiceLoader($container, $this->bundlePath . '/config');
        $loader->load('services.php');
        $loader->load('{services}/*.php', 'glob');

        if (class_exists(DoctrineFixturesBundle::class)) {
            $loader->load('data_fixtures.php');
        }

        $container->registerForAutoconfiguration(Capability::class)
            ->addTag('park_manager.webhosting_capability');
        $container->registerForAutoconfiguration(CapabilityGuard::class)
            ->addTag('park_manager.webhosting_capability_guard');
        $container->registerForAutoconfiguration(CapabilityConfigurationApplier::class)
            ->addTag('park_manager.webhosting_capability_config_applier');
    }

    public function getAlias(): string
    {
        return self::EXTENSION_ALIAS;
    }

    public function prepend(ContainerBuilder $container)
    {
        $this->initBundlePath();
        $this->registerDoctrineDbalTypes($container, $this->bundlePath . '/src');
    }
}
