<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Tests\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use ParkManager\Bundle\WebhostingBundle\DependencyInjection\Compiler\ConstraintsFactoryPass;
use ParkManager\Bundle\WebhostingBundle\Plan\ConstraintsFactory;
use ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanConstraint\MonthlyTrafficQuota;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class ConstraintsFactoryPassTest extends AbstractCompilerPassTestCase
{
    /** @test */
    public function it_compiles_with_no_ConstraintsFactory_registered(): void
    {
        $this->container->setParameter(
            'park_manager.webhosting.plan_constraints',
            ['MonthlyTrafficQuota' => MonthlyTrafficQuota::class]
        );

        $this->compile();

        $this->assertContainerBuilderNotHasService(ConstraintsFactory::class);
    }

    /** @test */
    public function it_sets_ConstraintsFactory_mapping(): void
    {
        $this->container->setParameter(
            'park_manager.webhosting.plan_constraints',
            ['MonthlyTrafficQuota' => MonthlyTrafficQuota::class]
        );
        $this->container->register(ConstraintsFactory::class);
        $this->compile();

        $this->assertContainerBuilderHasService(ConstraintsFactory::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            ConstraintsFactory::class,
            0,
            [MonthlyTrafficQuota::id() => MonthlyTrafficQuota::class]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ConstraintsFactoryPass());
    }
}
