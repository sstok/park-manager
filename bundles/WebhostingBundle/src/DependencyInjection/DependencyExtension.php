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
use ParkManager\Bundle\WebhostingBundle\DependencyInjection\Compiler\PlanConstraintsPass;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraint;
use ParkManager\Bundle\WebhostingBundle\Plan\ConstraintApplier;
use ParkManager\Bundle\WebhostingBundle\Plan\ConstraintValidator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

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

        if (\class_exists(DoctrineFixturesBundle::class)) {
            $loader->load('data_fixtures.php');
        }

        $container->registerForAutoconfiguration(Constraint::class)
            ->addTag(PlanConstraintsPass::CONSTRAINT_TAG)
        ;
        $container->registerForAutoconfiguration(ConstraintValidator::class)
            ->addTag(PlanConstraintsPass::CONSTRAINT_VALIDATOR_TAG)
        ;
        $container->registerForAutoconfiguration(ConstraintApplier::class)
            ->addTag(PlanConstraintsPass::CONSTRAINT_APPLIER_TAG)
        ;
    }

    public function getAlias(): string
    {
        return self::EXTENSION_ALIAS;
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->initBundlePath();
        $this->registerDoctrineDbalTypes($container, $this->bundlePath . '/src');
    }
}
