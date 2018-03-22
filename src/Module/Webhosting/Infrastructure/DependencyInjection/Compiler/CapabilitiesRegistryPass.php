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

namespace ParkManager\Module\Webhosting\Infrastructure\DependencyInjection\Compiler;

use ParkManager\Module\Webhosting\Domain\Package\Capability;
use ParkManager\Module\Webhosting\Domain\Package\CapabilityGuard;
use ParkManager\Module\Webhosting\Domain\Package\ConfigurationApplier;
use ParkManager\Module\Webhosting\Service\Package\CapabilitiesRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class CapabilitiesRegistryPass implements CompilerPassInterface
{
    /**
     * @var ContainerBuilder|null
     */
    private $container;

    /**
     * @var ParameterBagInterface|null
     */
    private $parameters;

    private $capabilities = [];
    private $capabilitiesById = [];
    private $guardServices = [];
    private $applierServices = [];

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(CapabilitiesRegistry::class)) {
            return;
        }

        $this->container = $container;
        $this->parameters = $container->getParameterBag();

        foreach ($container->findTaggedServiceIds('park_manager.webhosting_capability') as $serviceId => $tags) {
            $def = $container->findDefinition($serviceId)->setAutowired(false);

            $class = $this->parameters->resolveValue($def->getClass());
            $class = $this->resolveClassService($class, $class, Capability::class);
            $this->processCapability($class, $tags[0]);
        }

        $managerService = $container->findDefinition(CapabilitiesRegistry::class);
        $managerService->setArguments(
            [
                $this->capabilities,
                $this->capabilitiesById,
                ServiceLocatorTagPass::register($this->container, $this->guardServices),
                ServiceLocatorTagPass::register($this->container, $this->applierServices),
            ]
        );

        $this->parameters = $this->container = null;
        $this->capabilities = $this->guardServices = $this->applierServices = [];
    }

    private function processCapability(string $capabilityName, array $config): void
    {
        $config = $this->parameters->resolveValue($config);

        $namespaceParts = explode('\\', $capabilityName);
        $capabilityNameUnderscored = Container::underscore($namespaceParts[\count($namespaceParts) - 1]);
        $this->validateCapabilityConfig($capabilityName, $config);

        $config['guard'] = isset($config['guard']) ? $this->resolveClassService($capabilityName, (string) $config['guard'], CapabilityGuard::class) : null;
        $config['applier'] = isset($config['applier']) ? $this->resolveClassService($capabilityName, (string) $config['applier'], ConfigurationApplier::class) : null;
        $config['twig'] = [
            'edit' => sprintf('@Webhosting/capabilities/edit_%s.html.twig', $capabilityNameUnderscored),
            'show' => sprintf('@Webhosting/capabilities/show_%s.html.twig', $capabilityNameUnderscored),
        ];
        $config['jsx'] = [
            'edit' => sprintf('@Webhosting/capabilities/edit_%s.html.jsx', $capabilityNameUnderscored),
            'show' => sprintf('@Webhosting/capabilities/show_%s.html.jsx', $capabilityNameUnderscored),
        ];

        $this->capabilities[$capabilityName] = $config;
        $this->capabilitiesById[$capabilityName::id()] = $capabilityName;

        if (isset($config['guard'])) {
            $this->guardServices[$capabilityName] = new Reference($config['guard']);
        }

        if (isset($config['applier'])) {
            $this->applierServices[$capabilityName] = new Reference($config['applier']);
        }
    }

    private function resolveClassService(string $capabilityName, string $className, string $expectedInterface): string
    {
        /** @var string $className */
        $className = $this->parameters->resolveValue($className);

        if (!is_string($className)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Webhosting Capability %s is incorrectly configured. Class %s is not a string parameter.',
                    $capabilityName,
                    $className
                )
            );
        }

        if (!class_exists($className)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Webhosting Capability %s is incorrectly configured. Class %s cannot be found.',
                    $capabilityName,
                    $className
                )
            );
        }

        if (!is_a($className, $expectedInterface, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Webhosting Capability %s is incorrectly configured. Class %s does not implement interface %s.',
                    $capabilityName,
                    $className,
                    $expectedInterface
                )
            );
        }

        if (!$this->container->has($className)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Webhosting Capability %s is incorrectly configured. Class %s is not (correctly) registered in the service container.',
                    $capabilityName,
                    $className
                )
            );
        }

        return $className;
    }

    private function validateCapabilityConfig(string $capabilityName, array $config): void
    {
        $allowed = ['guard', 'applier', 'form-type'];
        if (\count($unknown = array_diff(array_keys($config), $allowed))) {
            throw new InvalidArgumentException(
                sprintf(
                    'Webhosting Capability %s is incorrectly configured. Unexpected attribute(s): %s. Supported: %s',
                    $capabilityName,
                    implode(', ', $unknown),
                    implode(', ', $allowed)
                )
            );
        }

        if (!isset($config['guard']) && !isset($config['applier'])) {
            throw new InvalidArgumentException(sprintf('Webhosting Capability %s is incorrectly configured. Requires a "guard" and/or "applier".', $capabilityName));
        }

        if (!isset($config['form-type'])) {
            throw new InvalidArgumentException(sprintf('Webhosting Capability %s is incorrectly configured. Requires a "form-type".', $capabilityName));
        }
    }
}
