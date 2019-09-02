<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\DependencyInjection\Compiler;

use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraint;
use ParkManager\Bundle\WebhostingBundle\Plan\ConstraintApplier;
use ParkManager\Bundle\WebhostingBundle\Plan\ConstraintValidator;
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

final class PlanConstraintsPass implements CompilerPassInterface
{
    private $constraints       = [];
    private $validatorServices = [];
    private $applierServices   = [];

    public const CONSTRAINT_TAG           = 'park_manager.webhosting_plan_constraint';
    public const CONSTRAINT_VALIDATOR_TAG = 'park_manager.webhosting_plan_constraint_validator';
    public const CONSTRAINT_APPLIER_TAG   = 'park_manager.webhosting_plan_constraint_config_applier';

    public function process(ContainerBuilder $container): void
    {
        $this->collectConstraintValidators($container);
        $this->collectConstraintAppliers($container);
        $this->collectConstraint($container);

        $container->setAlias(
            'park_manager.webhosting.plan_constraint_validators',
            (string) ServiceLocatorTagPass::register($container, $this->validatorServices)
        );
        $container->setAlias(
            'park_manager.webhosting.plan_constraint_appliers',
            (string) ServiceLocatorTagPass::register($container, $this->applierServices)
        );
        $container->setParameter('park_manager.webhosting.plan_constraints', $this->constraints);
    }

    private function collectConstraintValidators(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds(self::CONSTRAINT_VALIDATOR_TAG) as $class => $tags) {
            $name = $this->getClassName($class, 'Validator');
            $this->assertServiceClass($name, $class, ConstraintValidator::class);
            $this->validatorServices[$name] = new Reference($class);
        }
    }

    private function collectConstraintAppliers(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds(self::CONSTRAINT_APPLIER_TAG) as $class => $tags) {
            $name = $this->getClassName($class, 'Applier');
            $this->assertServiceClass($name, $class, ConstraintApplier::class);
            $this->applierServices[$name] = new Reference($class);
        }
    }

    private function collectConstraint(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds(self::CONSTRAINT_TAG) as $class => $tags) {
            $name = $this->getClassName($class);
            $this->assertServiceClass($name, $class, Constraint::class);

            if (isset($this->validatorServices[$name], $this->applierServices[$name])) {
                throw new InvalidArgumentException(
                    sprintf('Webhosting Plan Constraint "%s" can not have a Validator *and* Applier.', $name)
                );
            }

            if (! isset($this->validatorServices[$name]) && ! isset($this->applierServices[$name])) {
                throw new InvalidArgumentException(
                    sprintf('Webhosting Plan Constraint "%s" requires a %1$sValidator *or* %1$sApplier is registered.', $name)
                );
            }

            $this->constraints[$name] = $class;
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

    private function assertServiceClass(string $constraintName, string $className, string $expectedInterface): void
    {
        if (! class_exists($className)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Webhosting Plan Constraint %s is incorrectly configured. Class %s cannot be found.',
                    $constraintName,
                    $className
                )
            );
        }

        if (! is_a($className, $expectedInterface, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Webhosting Plan Constraint %s is incorrectly configured. Class %s does not implement interface %s.',
                    $constraintName,
                    $className,
                    $expectedInterface
                )
            );
        }
    }
}
