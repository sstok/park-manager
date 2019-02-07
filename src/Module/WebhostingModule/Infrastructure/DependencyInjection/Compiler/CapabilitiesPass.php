<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Infrastructure\DependencyInjection\Compiler;

use ParkManager\Module\WebhostingModule\Application\Service\Package\PackageConfigurationApplier;
use ParkManager\Module\WebhostingModule\Domain\Package\Capability;
use ParkManager\Module\WebhostingModule\Infrastructure\Service\Package\CapabilityGuard;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use function class_exists;
use function is_a;
use function mb_strlen;
use function mb_strrpos;
use function mb_substr;
use function sprintf;

final class CapabilitiesPass implements CompilerPassInterface
{
    private $guardServices   = [];
    private $applierServices = [];
    private $capabilities    = [];

    public const CAPABILITY_TAG                = 'park_manager.webhosting_capability';
    public const CAPABILITY_GUARD_TAG          = 'park_manager.webhosting_capability_guard';
    public const CAPABILITY_CONFIG_APPLIER_TAG = 'park_manager.webhosting_capability_config_applier';

    public function process(ContainerBuilder $container): void
    {
        $this->collectCapabilityGuards($container);
        $this->collectCapabilityAppliers($container);
        $this->collectCapabilities($container);

        $container->setAlias(
            'park_manager.webhosting.package_capability_guards',
            (string) ServiceLocatorTagPass::register($container, $this->guardServices)
        );
        $container->setAlias(
            'park_manager.webhosting.package_capability_configuration_appliers',
            (string) ServiceLocatorTagPass::register($container, $this->applierServices)
        );
        $container->setParameter('park_manager.webhosting.package_capabilities', $this->capabilities);
    }

    private function collectCapabilityGuards(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds(self::CAPABILITY_GUARD_TAG) as $class => $tags) {
            $name = $this->getClassName($class, 'Guard');
            $this->assertServiceClass($name, $class, CapabilityGuard::class);
            $this->guardServices[$name] = new Reference($class);
        }
    }

    private function collectCapabilityAppliers(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds(self::CAPABILITY_CONFIG_APPLIER_TAG) as $class => $tags) {
            $name = $this->getClassName($class, 'Applier');
            $this->assertServiceClass($name, $class, PackageConfigurationApplier::class);
            $this->applierServices[$name] = new Reference($class);
        }
    }

    private function collectCapabilities(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds(self::CAPABILITY_TAG) as $class => $tags) {
            $name = $this->getClassName($class);
            $this->assertServiceClass($name, $class, Capability::class);

            if (isset($this->guardServices[$name], $this->applierServices[$name])) {
                throw new InvalidArgumentException(
                    sprintf('Webhosting Capability "%s" can not have a Guard *and* Applier.', $name)
                );
            }

            if (! isset($this->guardServices[$name]) && ! isset($this->applierServices[$name])) {
                throw new InvalidArgumentException(
                    sprintf('Webhosting Capability "%s" requires a %1$sGuard *or* %1$sApplier is registered.', $name)
                );
            }

            $this->capabilities[$name] = $class;
            $container->removeDefinition($class);
        }
    }

    private function getClassName(string $class, ?string $suffix = null): string
    {
        if ($suffix !== null) {
            $class = mb_substr($class, 0, -mb_strlen($suffix));
        }

        return mb_substr($class, mb_strrpos($class, '\\') + 1);
    }

    private function assertServiceClass(string $capabilityName, string $className, string $expectedInterface): void
    {
        if (! class_exists($className)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Webhosting Capability %s is incorrectly configured. Class %s cannot be found.',
                    $capabilityName,
                    $className
                )
            );
        }

        if (! is_a($className, $expectedInterface, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Webhosting Capability %s is incorrectly configured. Class %s does not implement interface %s.',
                    $capabilityName,
                    $className,
                    $expectedInterface
                )
            );
        }
    }
}
