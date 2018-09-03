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

namespace ParkManager\Module\WebhostingModule\Tests\Infrastructure\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use ParkManager\Module\WebhostingModule\Infrastructure\DependencyInjection\Compiler\CapabilitiesFactoryPass;
use ParkManager\Module\WebhostingModule\Infrastructure\Service\Package\CapabilitiesFactory;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Domain\PackageCapability\MonthlyTrafficQuota;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class CapabilitiesFactoryPassTest extends AbstractCompilerPassTestCase
{
    /**
     * @test
     */
    public function it_compiles_with_no_CapabilitiesFactory_registered()
    {
        $this->container->setParameter(
            'park_manager.webhosting.package_capabilities',
            ['MonthlyTrafficQuota' => MonthlyTrafficQuota::class]
        );

        $this->compile();

        $this->assertContainerBuilderNotHasService(CapabilitiesFactory::class);
    }

    /**
     * @test
     */
    public function it_sets_CapabilitiesFactory_mapping()
    {
        $this->container->setParameter(
            'park_manager.webhosting.package_capabilities',
            ['MonthlyTrafficQuota' => MonthlyTrafficQuota::class]
        );
        $this->container->register(CapabilitiesFactory::class);
        $this->compile();

        $this->assertContainerBuilderHasService(CapabilitiesFactory::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            CapabilitiesFactory::class,
            0,
            [MonthlyTrafficQuota::id() => MonthlyTrafficQuota::class]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CapabilitiesFactoryPass());
    }
}
