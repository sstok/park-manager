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
    public function it_processes_capabilities()
    {
        $this->setParameter('bar', 'beer');

        $this->registerService(MonthlyTrafficQuotaGuard2::class, MonthlyTrafficQuotaGuard2::class);
        $this->registerService(MonthlyTrafficQuota::class, MonthlyTrafficQuota::class)->addTag(
            'park_manager.webhosting_capability',
            [
                'guard' => MonthlyTrafficQuotaGuard2::class,
                'applier' => null,
                'form-type' => 'foo',
            ]
        );

        $this->compileForManager();
        $this->assertManagerServiceConfigEquals(
            [
                MonthlyTrafficQuota::class => [
                    'guard' => MonthlyTrafficQuotaGuard2::class,
                    'applier' => null,
                    'form-type' => 'foo',
                    'twig' => [
                        'edit' => '@Webhosting/capabilities/edit_monthly_traffic_quota.html.twig',
                        'show' => '@Webhosting/capabilities/show_monthly_traffic_quota.html.twig',
                    ],
                    'jsx' => [
                        'edit' => '@Webhosting/capabilities/edit_monthly_traffic_quota.html.jsx',
                        'show' => '@Webhosting/capabilities/show_monthly_traffic_quota.html.jsx',
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
    public function it_validates_configuration(array $config, string $message)
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
                    'form-type' => 'foo',
                ],
                'Webhosting Capability '.MonthlyTrafficQuota::class.' is incorrectly configured. Requires a "guard" and/or "applier".',
            ],
            [
                [
                    'guard' => null,
                    'applier' => MonthlyTrafficQuotaGuard::class,
                    'form-type' => 'foo',
                ],
                'Webhosting Capability '.MonthlyTrafficQuota::class.' is incorrectly configured. Class '.
                MonthlyTrafficQuotaGuard::class.' does not implement interface '.ConfigurationApplier::class.'.',
            ],
            [
                [
                    'guard' => StorageSpaceQuotaApplier::class,
                    'applier' => null,
                    'form-type' => 'foo',
                ],
                'Webhosting Capability '.MonthlyTrafficQuota::class.' is incorrectly configured. Class '.
                StorageSpaceQuotaApplier::class.' does not implement interface '.CapabilityGuard::class.'.',
            ],
            [
                [
                    'guard' => MonthlyTrafficQuotaGuard2::class,
                    'applier' => null,
                    'form-type' => 'foo',
                ],
                'Webhosting Capability '.MonthlyTrafficQuota::class.' is incorrectly configured. Class '.
                MonthlyTrafficQuotaGuard2::class.' is not (correctly) registered in the service container.',
            ],
            [
                [
                    'guard' => 'NopeNopeNopeAcmeGuard',
                    'applier' => null,
                    'form-type' => 'foo',
                ],
                'Webhosting Capability '.MonthlyTrafficQuota::class.' is incorrectly configured. Class NopeNopeNopeAcmeGuard cannot be found.',
            ],
            [
                [
                    'guard' => MonthlyTrafficQuotaGuard::class,
                    'applier' => null,
                    'foo' => '',
                ],
                'Webhosting Capability '.MonthlyTrafficQuota::class.' is incorrectly configured. Unexpected attribute(s): foo. Supported: guard, applier, form-type',
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
