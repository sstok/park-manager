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

namespace ParkManager\Module\Webhosting\Tests\Infrastructure\Symfony\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use ParkManager\Bridge\PhpUnit\DefinitionArgumentEqualsServiceLocatorConstraint;
use ParkManager\Module\Webhosting\Infrastructure\Symfony\DependencyInjection\Compiler\CapabilitiesRegistryPass;
use ParkManager\Module\Webhosting\Model\Package\CapabilityGuard;
use ParkManager\Module\Webhosting\Model\Package\ConfigurationApplier;
use ParkManager\Module\Webhosting\Service\Package\CapabilitiesRegistry;
use ParkManager\Module\Webhosting\Tests\Fixtures\Infrastructure\Package\Capability\MonthlyTrafficQuotaGuard;
use ParkManager\Module\Webhosting\Tests\Fixtures\Infrastructure\Package\Capability\MonthlyTrafficQuotaGuard2;
use ParkManager\Module\Webhosting\Tests\Fixtures\Infrastructure\Package\Capability\StorageSpaceQuotaApplier;
use ParkManager\Module\Webhosting\Tests\Fixtures\Model\Package\Capability\MonthlyTrafficQuota;
use ParkManager\Module\Webhosting\Tests\Fixtures\Model\Package\Capability\StorageSpaceQuota;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class CapabilitiesRegistryPassTest extends AbstractCompilerPassTestCase
{
    /**
     * @test
     */
    public function it_compiles_with_no_capabilities_registered()
    {
        $this->compileForManager();
        $this->assertManagerServiceConfigEquals([], [], [], []);
    }

    /**
     * @test
     */
    public function it_processes_auto_configuration_of_capabilities()
    {
        $this->registerService(MonthlyTrafficQuotaGuard::class, MonthlyTrafficQuotaGuard::class);
        $this->registerService(StorageSpaceQuotaApplier::class, StorageSpaceQuotaApplier::class);
        $this->registerService(MonthlyTrafficQuota::class, MonthlyTrafficQuota::class)->addTag('park_manager.webhosting_capability', ['auto-configure' => true]);
        $this->registerService(StorageSpaceQuota::class, StorageSpaceQuota::class)->addTag('park_manager.webhosting_capability');

        $this->compileForManager();
        $this->assertManagerServiceConfigEquals(
            [
                MonthlyTrafficQuota::class => [
                    'guard' => MonthlyTrafficQuotaGuard::class,
                    'applier' => null,
                    'form' => ['type' => null, 'options' => []],
                    'twig' => [
                        'edit' => [
                            'file' => '@Webhosting/capabilities/edit_monthly_traffic_quota.html.twig',
                            'context' => [],
                        ],
                        'show' => [
                            'file' => '@Webhosting/capabilities/show_monthly_traffic_quota.html.twig',
                            'context' => [],
                        ],
                    ],
                    'jsx' => [
                        'edit' => [
                            'file' => '@Webhosting/capabilities/edit_monthly_traffic_quota.html.jsx',
                            'context' => [],
                        ],
                        'show' => [
                            'file' => '@Webhosting/capabilities/show_monthly_traffic_quota.html.jsx',
                            'context' => [],
                        ],
                    ],
                ],
                StorageSpaceQuota::class => [
                    'guard' => null,
                    'applier' => StorageSpaceQuotaApplier::class,
                    'form' => ['type' => null, 'options' => []],
                    'twig' => [
                        'edit' => [
                            'file' => '@Webhosting/capabilities/edit_storage_space_quota.html.twig',
                            'context' => [],
                        ],
                        'show' => [
                            'file' => '@Webhosting/capabilities/show_storage_space_quota.html.twig',
                            'context' => [],
                        ],
                    ],
                    'jsx' => [
                        'edit' => [
                            'file' => '@Webhosting/capabilities/edit_storage_space_quota.html.jsx',
                            'context' => [],
                        ],
                        'show' => [
                            'file' => '@Webhosting/capabilities/show_storage_space_quota.html.jsx',
                            'context' => [],
                        ],
                    ],
                ],
            ],
            [
                MonthlyTrafficQuota::id() => MonthlyTrafficQuota::class,
                StorageSpaceQuota::id() => StorageSpaceQuota::class,
            ],
            [MonthlyTrafficQuota::class => MonthlyTrafficQuotaGuard::class],
            [StorageSpaceQuota::class => StorageSpaceQuotaApplier::class]
        );
    }

    /**
     * @test
     */
    public function it_processes_manual_configuration_of_capabilities()
    {
        $this->setParameter('bar', 'beer');

        $this->registerService(MonthlyTrafficQuotaGuard2::class, MonthlyTrafficQuotaGuard2::class);

        $this->registerService(MonthlyTrafficQuota::class, MonthlyTrafficQuota::class)->addTag(
            'park_manager.webhosting_capability',
            [
                'auto-configure' => false,
                'guard' => MonthlyTrafficQuotaGuard2::class,
                'applier' => null,
                'form' => ['type' => null, 'options' => ['foo' => '%bar%']],
                'twig' => [
                    'edit' => [
                        'file' => '@Webhosting/capabilities/edit_monthly_traffic_quota.html.twig',
                        'context' => [],
                    ],
                    'show' => '@Webhosting/capabilities/show_monthly_traffic_quota.html.twig',
                ],
            ]
        );

        $this->compileForManager();
        $this->assertManagerServiceConfigEquals(
            [
                MonthlyTrafficQuota::class => [
                    'guard' => MonthlyTrafficQuotaGuard2::class,
                    'applier' => null,
                    'form' => ['type' => null, 'options' => ['foo' => 'beer']],
                    'twig' => [
                        'edit' => [
                            'file' => '@Webhosting/capabilities/edit_monthly_traffic_quota.html.twig',
                            'context' => [],
                        ],
                        'show' => [
                            'file' => '@Webhosting/capabilities/show_monthly_traffic_quota.html.twig',
                            'context' => [],
                        ],
                    ],
                    'jsx' => [
                        'edit' => [
                            'file' => null,
                            'context' => [],
                        ],
                        'show' => [
                            'file' => null,
                            'context' => [],
                        ],
                    ],
                ],
            ],
            [MonthlyTrafficQuota::id() => MonthlyTrafficQuota::class],
            [MonthlyTrafficQuota::class => MonthlyTrafficQuotaGuard2::class],
            []
        );
    }

    /**
     * @test
     * @dataProvider provideInvalidConfiguration
     */
    public function it_validates_manual_configuration(array $config, string $message)
    {
        $this->registerService(MonthlyTrafficQuotaGuard::class, MonthlyTrafficQuotaGuard::class);
        $this->registerService(StorageSpaceQuotaApplier::class, StorageSpaceQuotaApplier::class);
        $this->registerService(MonthlyTrafficQuota::class, MonthlyTrafficQuota::class)
            ->addTag('park_manager.webhosting_capability', $config);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $this->compileForManager();
    }

    public function provideInvalidConfiguration(): iterable
    {
        return [
            [
                [
                    'guard' => null,
                    'applier' => null,
                    'form' => ['type' => null, 'options' => ['foo' => '']],
                    'twig' => [
                        'edit' => [
                            'file' => '@Webhosting/capabilities/edit_monthly_traffic_quota.html.twig',
                            'context' => [],
                        ],
                    ],
                ],
                'Webhosting Capability '.MonthlyTrafficQuota::class.' is incorrectly configured. Missing a required Guard and/or Applier.',
            ],
            [
                [
                    'guard' => null,
                    'applier' => MonthlyTrafficQuotaGuard::class,
                    'form' => ['type' => null, 'options' => ['foo' => '']],
                    'twig' => [
                        'edit' => [
                            'file' => '@Webhosting/capabilities/edit_monthly_traffic_quota.html.twig',
                            'context' => [],
                        ],
                    ],
                ],
                'Webhosting Capability '.MonthlyTrafficQuota::class.' is incorrectly configured. Class '.
                MonthlyTrafficQuotaGuard::class.' does not implement interface '.ConfigurationApplier::class.'.',
            ],
            [
                [
                    'guard' => StorageSpaceQuotaApplier::class,
                    'applier' => null,
                    'form' => ['type' => null, 'options' => ['foo' => '']],
                    'twig' => [
                        'edit' => [
                            'file' => '@Webhosting/capabilities/edit_monthly_traffic_quota.html.twig',
                            'context' => [],
                        ],
                    ],
                ],
                'Webhosting Capability '.MonthlyTrafficQuota::class.' is incorrectly configured. Class '.
                StorageSpaceQuotaApplier::class.' does not implement interface '.CapabilityGuard::class.'.',
            ],
            [
                [
                    'guard' => MonthlyTrafficQuotaGuard2::class,
                    'applier' => null,
                    'form' => ['type' => null, 'options' => ['foo' => '']],
                    'twig' => [
                        'edit' => [
                            'file' => '@Webhosting/capabilities/edit_monthly_traffic_quota.html.twig',
                            'context' => [],
                        ],
                    ],
                ],
                'Webhosting Capability '.MonthlyTrafficQuota::class.' is incorrectly configured. Class '.
                MonthlyTrafficQuotaGuard2::class.' is not (correctly) registered in the service container.',
            ],
            [
                [
                    'guard' => 'NopeNopeNopeAcmeGuard',
                    'applier' => null,
                    'form' => ['type' => null, 'options' => ['foo' => '']],
                    'twig' => [
                        'edit' => [
                            'file' => '@Webhosting/capabilities/edit_monthly_traffic_quota.html.twig',
                            'context' => [],
                        ],
                    ],
                ],
                'Webhosting Capability '.MonthlyTrafficQuota::class.' is incorrectly configured. Class NopeNopeNopeAcmeGuard cannot be found.',
            ],
            [
                [
                    'guard' => MonthlyTrafficQuotaGuard::class,
                    'applier' => null,
                    'form' => ['type' => null, 'options' => ['foo' => ''], 'foo' => 'nope'],
                ],
                'Webhosting Capability '.MonthlyTrafficQuota::class.' is incorrectly configured with attribute "form". Unexpected option(s): foo. Supported: type, options',
            ],
            [
                [
                    'guard' => MonthlyTrafficQuotaGuard::class,
                    'applier' => null,
                    'form' => ['options' => 'not-supported'],
                ],
                'Webhosting Capability '.MonthlyTrafficQuota::class.' is incorrectly configured with attribute "form". "options" must be an array.',
            ],
            [
                [
                    'guard' => MonthlyTrafficQuotaGuard::class,
                    'applier' => null,
                    'twig' => [
                        'edit' => [
                            'file' => '@Webhosting/capabilities/edit_monthly_traffic_quota.html.twig',
                            'context' => [],
                            'options' => 'not-supported',
                        ],
                    ],
                ],
                'Webhosting Capability '.MonthlyTrafficQuota::class.' is incorrectly configured with attribute "twig.edit". Unexpected option(s): options. Supported: file, context',
            ],
            [
                [
                    'guard' => MonthlyTrafficQuotaGuard::class,
                    'applier' => null,
                    'twig' => [
                        'edit' => [
                            'file' => '@Webhosting/capabilities/edit_monthly_traffic_quota.html.twig',
                            'context' => 'not-supported',
                        ],
                    ],
                ],
                'Webhosting Capability '.MonthlyTrafficQuota::class.' is incorrectly configured with attribute "twig.edit". "context" must be an array.',
            ],
            [
                [
                    'guard' => MonthlyTrafficQuotaGuard::class,
                    'applier' => null,
                    'jsx' => [
                        'edit' => [
                            'file' => '@Webhosting/capabilities/edit_monthly_traffic_quota.html.jsx',
                            'context' => [],
                            'options' => 'not-supported',
                        ],
                    ],
                ],
                'Webhosting Capability '.MonthlyTrafficQuota::class.' is incorrectly configured with attribute "jsx.edit". Unexpected option(s): options. Supported: file, context',
            ],
        ];
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CapabilitiesRegistryPass());
    }

    /**
     * Assert that the ContainerBuilder for this test has a service definition with the given id, which has an argument
     * at the given index, and its value is a ServiceLocator with a ref-map equal to the given value.
     *
     * @param string      $serviceId
     * @param int         $argumentIndex
     * @param Reference[] $expectedValue
     */
    protected function assertContainerBuilderHasServiceDefinitionWithArgumentServiceLocator(
        $serviceId,
        $argumentIndex,
        array $expectedValue
    ) {
        $definition = $this->container->findDefinition($serviceId);

        self::assertThat(
            $definition,
            new DefinitionArgumentEqualsServiceLocatorConstraint($this->container, $argumentIndex, $expectedValue)
        );
    }

    private function assertManagerServiceConfigEquals(array $caps, array $ids, array $guardServices, array $applierServices): void
    {
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(CapabilitiesRegistry::class, 0, $caps);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(CapabilitiesRegistry::class, 1, $ids);
        $this->assertContainerBuilderHasServiceDefinitionWithArgumentServiceLocator(CapabilitiesRegistry::class, 2, $guardServices);
        $this->assertContainerBuilderHasServiceDefinitionWithArgumentServiceLocator(CapabilitiesRegistry::class, 3, $applierServices);
    }

    private function compileForManager(): void
    {
        $this->registerService(CapabilitiesRegistry::class, CapabilitiesRegistry::class);
        $this->compile();
    }
}
