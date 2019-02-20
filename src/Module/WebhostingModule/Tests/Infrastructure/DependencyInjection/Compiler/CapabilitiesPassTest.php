<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Tests\Infrastructure\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use ParkManager\Module\WebhostingModule\Infrastructure\DependencyInjection\Compiler\CapabilitiesPass;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Domain\PackageCapability\MonthlyTrafficQuota;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Infrastructure\PackageCapability\MonthlyTrafficQuotaApplier;
use ParkManager\Module\WebhostingModule\Tests\Fixtures\Infrastructure\PackageCapability\MonthlyTrafficQuotaGuard;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * @internal
 */
final class CapabilitiesPassTest extends AbstractCompilerPassTestCase
{
    /** @test */
    public function it_compiles_with_no_capabilities_registered(): void
    {
        $this->compile();

        $this->assertContainerBuilderNotHasService(MonthlyTrafficQuota::class);
        $this->assertContainerBuilderHasParameter('park_manager.webhosting.package_capabilities', []);
        $this->assertContainerBuilderHasServiceLocator(
            'park_manager.webhosting.package_capability_guards',
            []
        );
        $this->assertContainerBuilderHasServiceLocator(
            'park_manager.webhosting.package_capability_configuration_appliers',
            []
        );
    }

    /** @test */
    public function it_processes_capabilities_with_guards(): void
    {
        $this->registerService(MonthlyTrafficQuotaGuard::class, MonthlyTrafficQuotaGuard::class)
            ->addTag(CapabilitiesPass::CAPABILITY_GUARD_TAG);
        $this->registerService(MonthlyTrafficQuota::class, MonthlyTrafficQuota::class)
            ->addTag(CapabilitiesPass::CAPABILITY_TAG);

        $this->compile();

        $this->assertContainerBuilderNotHasService(MonthlyTrafficQuota::class);
        $this->assertContainerBuilderHasParameter('park_manager.webhosting.package_capabilities', [
            'MonthlyTrafficQuota' => MonthlyTrafficQuota::class,
        ]);
        $this->assertContainerBuilderHasServiceLocator(
            'park_manager.webhosting.package_capability_guards',
            ['MonthlyTrafficQuota' => MonthlyTrafficQuotaGuard::class]
        );
        $this->assertContainerBuilderHasServiceLocator(
            'park_manager.webhosting.package_capability_configuration_appliers',
            []
        );
    }

    /** @test */
    public function it_processes_capabilities_with_appliers(): void
    {
        $this->registerService(MonthlyTrafficQuotaApplier::class, MonthlyTrafficQuotaApplier::class)
            ->addTag(CapabilitiesPass::CAPABILITY_CONFIG_APPLIER_TAG);
        $this->registerService(MonthlyTrafficQuota::class, MonthlyTrafficQuota::class)
            ->addTag(CapabilitiesPass::CAPABILITY_TAG);

        $this->compile();

        $this->assertContainerBuilderNotHasService(MonthlyTrafficQuota::class);
        $this->assertContainerBuilderHasParameter('park_manager.webhosting.package_capabilities', [
            'MonthlyTrafficQuota' => MonthlyTrafficQuota::class,
        ]);
        $this->assertContainerBuilderHasServiceLocator(
            'park_manager.webhosting.package_capability_guards',
            []
        );
        $this->assertContainerBuilderHasServiceLocator(
            'park_manager.webhosting.package_capability_configuration_appliers',
            ['MonthlyTrafficQuota' => MonthlyTrafficQuotaApplier::class]
        );
    }

    /** @test */
    public function it_checks_a_guard_or_applier_is_set(): void
    {
        $this->registerService(MonthlyTrafficQuota::class, MonthlyTrafficQuota::class)
            ->addTag(CapabilitiesPass::CAPABILITY_TAG);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Webhosting Capability "MonthlyTrafficQuota" requires a ' .
            'MonthlyTrafficQuotaGuard *or* MonthlyTrafficQuotaApplier is registered.'
        );

        $this->compile();
    }

    /** @test */
    public function it_checks_only_a_guard_or_applier_is_set(): void
    {
        $this->registerService(MonthlyTrafficQuotaGuard::class, MonthlyTrafficQuotaGuard::class)
            ->addTag(CapabilitiesPass::CAPABILITY_GUARD_TAG);
        $this->registerService(MonthlyTrafficQuotaApplier::class, MonthlyTrafficQuotaApplier::class)
            ->addTag(CapabilitiesPass::CAPABILITY_CONFIG_APPLIER_TAG);
        $this->registerService(MonthlyTrafficQuota::class, MonthlyTrafficQuota::class)
            ->addTag(CapabilitiesPass::CAPABILITY_TAG);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Webhosting Capability "MonthlyTrafficQuota" can not have a Guard *and* Applier.');

        $this->compile();
    }

    /** @test */
    public function it_checks_service_class_exists(): void
    {
        $this->registerService('\MonthlyTrafficQuotaGuard', '\MonthlyTrafficQuotaGuard')
            ->addTag(CapabilitiesPass::CAPABILITY_GUARD_TAG);
        $this->registerService(MonthlyTrafficQuota::class, MonthlyTrafficQuota::class)
            ->addTag(CapabilitiesPass::CAPABILITY_TAG);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Webhosting Capability MonthlyTrafficQuota is incorrectly configured. ' .
            'Class \\MonthlyTrafficQuotaGuard cannot be found.'
        );

        $this->compile();
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CapabilitiesPass());
    }
}
