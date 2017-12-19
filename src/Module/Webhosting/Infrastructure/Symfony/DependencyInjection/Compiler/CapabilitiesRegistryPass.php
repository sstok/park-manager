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

namespace ParkManager\Module\Webhosting\Infrastructure\Symfony\DependencyInjection\Compiler;

use ParkManager\Module\Webhosting\Model\Package\Capability;
use ParkManager\Module\Webhosting\Model\Package\CapabilityGuard;
use ParkManager\Module\Webhosting\Model\Package\ConfigurationApplier;
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
            $def = $container->findDefinition($serviceId);
            $def->setAutowired(false);

            $capabilityName = $class = $this->parameters->resolveValue($def->getClass());
            $this->validateCapabilityClass($class);

            if (empty($tags[0]) || ($tags[0]['auto-configure'] ?? false)) {
                $config = $this->autoconfigureCapability($class);
            } else {
                unset($tags[0]['auto-configure']);
                $config = $this->processConfiguration($class, $tags[0]);
            }

            $this->capabilities[$capabilityName] = $config;
            $this->capabilitiesById[$capabilityName::id()] = $capabilityName;

            if (isset($config['guard'])) {
                $this->guardServices[$capabilityName] = new Reference($config['guard']);
            }

            if (isset($config['applier'])) {
                $this->applierServices[$capabilityName] = new Reference($config['applier']);
            }
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

    private function processConfiguration(string $capabilityName, array $config): array
    {
        $config = $this->parameters->resolveValue($config);

        if (!isset($config['guard']) && !isset($config['applier'])) {
            throw new InvalidArgumentException(sprintf('Webhosting Capability %s is incorrectly configured. Missing a required Guard and/or Applier.', $capabilityName));
        }

        $config['guard'] = isset($config['guard']) ? $this->resolveClassService($capabilityName, (string) $config['guard'], CapabilityGuard::class) : null;
        $config['applier'] = isset($config['applier']) ? $this->resolveClassService($capabilityName, (string) $config['applier'], ConfigurationApplier::class) : null;
        $config['form'] = $this->resolveFormConfig($capabilityName, $config);
        $config['twig'] = [
            'edit' => $this->resolveTemplateConfig($capabilityName, $config['twig']['edit'] ?? null, 'twig.edit'),
            'show' => $this->resolveTemplateConfig($capabilityName, $config['twig']['show'] ?? null, 'twig.show'),
        ];
        $config['jsx'] = [
            'edit' => $this->resolveTemplateConfig($capabilityName, $config['jsx']['edit'] ?? null, 'jsx.edit'),
            'show' => $this->resolveTemplateConfig($capabilityName, $config['jsx']['show'] ?? null, 'jsx.show'),
        ];

        $this->validateSupportedKeys($capabilityName, $config, ['guard', 'applier', 'form', 'twig', 'jsx']);
        $this->log(
            sprintf(
                'Capability %s is resolved with the following attributes: %s',
                $capabilityName,
                var_export($config, true)
            )
        );

        return $config;
    }

    private function autoconfigureCapability(string $className): array
    {
        $this->log(sprintf('Capability %s has no configuration, starting auto-configure.', $className));

        $namespaceParts = explode('\\', $className);
        $c = count($namespaceParts);

        if (($namespaceParts[$c - 4] ?? '') !== 'Model') {
            throw new \RuntimeException(
                sprintf(
                    'Unable to autoconfigure Webhosting Capability %s. '.
                    'Class does not follow expected convention. Configure service manually.',
                    $className
                )
            );
        }

        $capabilityName = array_slice($namespaceParts, -1)[0];
        $namespace = implode(array_slice($namespaceParts, 0, -4), '\\');
        $capabilityNameUnderscored = Container::underscore($capabilityName);

        $guardClass = sprintf('%s\\Infrastructure\Package\\Capability\\%sGuard', $namespace, $capabilityName);
        $applierClass = sprintf('%s\\Infrastructure\Package\\Capability\\%sApplier', $namespace, $capabilityName);
        $formType = sprintf('%s\\UI\\Web\\Form\\Package\\Capability\\%sType', $namespace, $capabilityName);

        $config = [];
        $config['guard'] = $this->findClassService($guardClass, CapabilityGuard::class);
        $config['applier'] = $this->findClassService($applierClass, ConfigurationApplier::class);
        $config['form'] = ['type' => class_exists($formType) ? $formType : null, 'options' => []];
        $config['twig'] = [
            'edit' => [
                'file' => sprintf('@Webhosting/capabilities/edit_%s.html.twig', $capabilityNameUnderscored),
                'context' => [],
            ],
            'show' => [
                'file' => sprintf('@Webhosting/capabilities/show_%s.html.twig', $capabilityNameUnderscored),
                'context' => [],
            ],
        ];
        $config['jsx'] = [
            'edit' => [
                'file' => sprintf('@Webhosting/capabilities/edit_%s.html.jsx', $capabilityNameUnderscored),
                'context' => [],
            ],
            'show' => [
                'file' => sprintf('@Webhosting/capabilities/show_%s.html.jsx', $capabilityNameUnderscored),
                'context' => [],
            ],
        ];

        if (!isset($config['guard']) && !isset($config['applier'])) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to auto-configure Webhosting Capability %s. '.
                    'Guard (%s) and/or Applier (%s) was expected but could not be located.'.
                    'Configure the service manually.',
                    $className,
                    $guardClass,
                    $applierClass
                )
            );
        }

        $this->log(
            sprintf(
                'Capability %s is auto-configured with the following attributes: %s',
                $className,
                var_export($config, true)
            )
        );

        return $config;
    }

    private function findClassService(string $className, string $expectedInterface): ?string
    {
        if (!class_exists($className)) {
            $this->log(sprintf('Unable to load class %s for auto auto-configure.', $className));

            return null;
        }

        if (!is_a($className, $expectedInterface, true)) {
            $this->log(
                sprintf(
                    'Class %s does not implement expected interface %s. Ignoring for auto-configure.',
                    $className,
                    $expectedInterface
                )
            );

            return null;
        }

        if (!$this->container->has($className)) {
            $this->log(
                sprintf(
                    'Class %s is not (correctly) registered in the service container. Ignoring for auto-configure.',
                    $className
                )
            );

            return null;
        }

        return $className;
    }

    private function resolveClassService(string $capabilityName, string $className, string $expectedInterface): string
    {
        $classNameResolved = $this->parameters->resolveValue($className);

        if (!is_string($className)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Webhosting Capability %s is incorrectly configured. Class %s is not a string parameter.',
                    $capabilityName,
                    $className
                )
            );
        }

        /** @var string $className */
        $className = $classNameResolved;

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

    private function resolveFormConfig(string $capabilityName, array $configuration): array
    {
        if (!isset($configuration['form'])) {
            return ['type' => null, 'options' => []];
        }

        if (is_string($configuration['form'])) {
            return ['type' => $configuration['form'], 'options' => []];
        }

        if (isset($configuration['form']['options']) && !is_array($configuration['form']['options'])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Webhosting Capability %s is incorrectly configured with attribute "form". "options" must be an array.',
                    $capabilityName
                )
            );
        }

        $this->validateSupportedKeys($capabilityName, $configuration['form'], ['type', 'options'], 'form');

        return $configuration['form'];
    }

    private function resolveTemplateConfig(string $capabilityName, $configuration, string $path): array
    {
        if (null === $configuration) {
            return ['file' => null, 'context' => []];
        }

        if (is_string($configuration)) {
            return ['file' => $configuration, 'context' => []];
        }

        if (isset($configuration['context']) && !is_array($configuration['context'])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Webhosting Capability %s is incorrectly configured with attribute "%s". "context" must be an array.',
                    $capabilityName,
                    $path
                )
            );
        }

        $this->validateSupportedKeys($capabilityName, $configuration, ['file', 'context'], $path);

        return $configuration;
    }

    private function validateSupportedKeys(string $capabilityName, array $config, array $allowed, ?string $path = null): void
    {
        if (count($unknown = array_diff(array_keys($config), $allowed))) {
            throw new InvalidArgumentException(
                sprintf(
                    'Webhosting Capability %s is incorrectly configured%s. Unexpected option(s): %s. Supported: %s',
                    $capabilityName,
                    $path ? ' with attribute "'.$path.'"' : '',
                    implode(', ', $unknown),
                    implode(', ', $allowed)
                )
            );
        }
    }

    private function log(string $message): void
    {
        $this->container->log($this, $message);
    }

    private function validateCapabilityClass(string $class): void
    {
        if (!is_a($class, Capability::class, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Webhosting Capability %s is invalid. Class must implement interface %s',
                    $class,
                    Capability::class
                )
            );
        }
    }
}
