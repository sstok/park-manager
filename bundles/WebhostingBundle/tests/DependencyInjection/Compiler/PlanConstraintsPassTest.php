<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Tests\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use ParkManager\Bundle\WebhostingBundle\DependencyInjection\Compiler\PlanConstraintsPass;
use ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanConstraint\MonthlyTrafficQuota;
use ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanConstraint\MonthlyTrafficQuotaApplier;
use ParkManager\Bundle\WebhostingBundle\Tests\Fixtures\PlanConstraint\MonthlyTrafficQuotaValidator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * @internal
 */
final class PlanConstraintsPassTest extends AbstractCompilerPassTestCase
{
    /** @test */
    public function it_compiles_with_no_constraints_registered(): void
    {
        $this->compile();

        $this->assertContainerBuilderNotHasService(MonthlyTrafficQuota::class);
        $this->assertContainerBuilderHasParameter('park_manager.webhosting.plan_constraints', []);
        $this->assertContainerBuilderHasServiceLocator(
            'park_manager.webhosting.plan_constraint_validators',
            []
        );
        $this->assertContainerBuilderHasServiceLocator(
            'park_manager.webhosting.plan_constraint_appliers',
            []
        );
    }

    /** @test */
    public function it_processes_constraints_with_validators(): void
    {
        $this->registerService(MonthlyTrafficQuotaValidator::class, MonthlyTrafficQuotaValidator::class)
            ->addTag(PlanConstraintsPass::CONSTRAINT_VALIDATOR_TAG);
        $this->registerService(MonthlyTrafficQuota::class, MonthlyTrafficQuota::class)
            ->addTag(PlanConstraintsPass::CONSTRAINT_TAG);

        $this->compile();

        $this->assertContainerBuilderNotHasService(MonthlyTrafficQuota::class);
        $this->assertContainerBuilderHasParameter('park_manager.webhosting.plan_constraints', [
            'MonthlyTrafficQuota' => MonthlyTrafficQuota::class,
        ]);
        $this->assertContainerBuilderHasServiceLocator(
            'park_manager.webhosting.plan_constraint_validators',
            ['MonthlyTrafficQuota' => MonthlyTrafficQuotaValidator::class]
        );
        $this->assertContainerBuilderHasServiceLocator(
            'park_manager.webhosting.plan_constraint_appliers',
            []
        );
    }

    /** @test */
    public function it_processes_constraints_with_appliers(): void
    {
        $this->registerService(MonthlyTrafficQuotaApplier::class, MonthlyTrafficQuotaApplier::class)
            ->addTag(PlanConstraintsPass::CONSTRAINT_APPLIER_TAG);
        $this->registerService(MonthlyTrafficQuota::class, MonthlyTrafficQuota::class)
            ->addTag(PlanConstraintsPass::CONSTRAINT_TAG);

        $this->compile();

        $this->assertContainerBuilderNotHasService(MonthlyTrafficQuota::class);
        $this->assertContainerBuilderHasParameter('park_manager.webhosting.plan_constraints', [
            'MonthlyTrafficQuota' => MonthlyTrafficQuota::class,
        ]);
        $this->assertContainerBuilderHasServiceLocator(
            'park_manager.webhosting.plan_constraint_validators',
            []
        );
        $this->assertContainerBuilderHasServiceLocator(
            'park_manager.webhosting.plan_constraint_appliers',
            ['MonthlyTrafficQuota' => MonthlyTrafficQuotaApplier::class]
        );
    }

    /** @test */
    public function it_checks_a_validator_or_applier_is_set(): void
    {
        $this->registerService(MonthlyTrafficQuota::class, MonthlyTrafficQuota::class)
            ->addTag(PlanConstraintsPass::CONSTRAINT_TAG);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Webhosting Plan Constraint "MonthlyTrafficQuota" requires a ' .
            'MonthlyTrafficQuotaValidator *or* MonthlyTrafficQuotaApplier is registered.'
        );

        $this->compile();
    }

    /** @test */
    public function it_checks_only_a_validator_or_applier_is_set(): void
    {
        $this->registerService(MonthlyTrafficQuotaValidator::class, MonthlyTrafficQuotaValidator::class)
            ->addTag(PlanConstraintsPass::CONSTRAINT_VALIDATOR_TAG);
        $this->registerService(MonthlyTrafficQuotaApplier::class, MonthlyTrafficQuotaApplier::class)
            ->addTag(PlanConstraintsPass::CONSTRAINT_APPLIER_TAG);
        $this->registerService(MonthlyTrafficQuota::class, MonthlyTrafficQuota::class)
            ->addTag(PlanConstraintsPass::CONSTRAINT_TAG);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Webhosting Plan Constraint "MonthlyTrafficQuota" can not have a Validator *and* Applier.');

        $this->compile();
    }

    /** @test */
    public function it_checks_service_class_exists(): void
    {
        $this->registerService('\MonthlyTrafficQuotaValidator', '\MonthlyTrafficQuotaValidator')
            ->addTag(PlanConstraintsPass::CONSTRAINT_VALIDATOR_TAG);
        $this->registerService(MonthlyTrafficQuota::class, MonthlyTrafficQuota::class)
            ->addTag(PlanConstraintsPass::CONSTRAINT_TAG);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Webhosting Plan Constraint MonthlyTrafficQuota is incorrectly configured. ' .
            'Class \\MonthlyTrafficQuotaValidator cannot be found.'
        );

        $this->compile();
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new PlanConstraintsPass());
    }
}
