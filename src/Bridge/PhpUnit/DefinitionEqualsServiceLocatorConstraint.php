<?php

declare(strict_types=1);

/*
 * This file is part of the Park-Manager project.
 *
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Bridge\PhpUnit;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsEqual;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use function array_map;
use function is_a;
use function is_string;
use function sprintf;

final class DefinitionEqualsServiceLocatorConstraint extends Constraint
{
    private $serviceId;
    private $expectedValue;

    public function __construct($serviceId, $expectedValue)
    {
        parent::__construct();

        $this->serviceId     = $serviceId;
        $this->expectedValue = array_map(function ($serviceId) {
            if (is_string($serviceId)) {
                return new ServiceClosureArgument(new Reference($serviceId));
            }

            if (! $serviceId instanceof ServiceClosureArgument) {
                return new ServiceClosureArgument($serviceId);
            }

            return $serviceId;
        }, $expectedValue);
    }

    public function toString(): string
    {
        return sprintf(
            'has a service definition "%s"',
            $this->serviceId
        );
    }

    public function evaluate($other, $description = '', $returnResult = false)
    {
        if (! ($other instanceof ContainerBuilder)) {
            throw new \InvalidArgumentException(
                'Expected an instance of Symfony\Component\DependencyInjection\ContainerBuilder'
            );
        }

        if (! $this->evaluateServiceDefinition($other, $returnResult)) {
            return false;
        }

        return true;
    }

    private function evaluateServiceDefinition(ContainerBuilder $containerBuilder, $returnResult)
    {
        if (! $containerBuilder->has($this->serviceId)) {
            if ($returnResult) {
                return false;
            }

            $this->fail(
                $containerBuilder,
                sprintf(
                    'The container builder has no service "%s"',
                    $this->serviceId
                )
            );
        }

        $definition  = $containerBuilder->findDefinition($this->serviceId);
        $actualClass = $containerBuilder->getParameterBag()->resolveValue($definition->getClass());

        if (! is_a($actualClass, ServiceLocator::class, true)) {
            $this->fail(
                $definition,
                sprintf(
                    'The class of the service definition of "%s" (%s) was expected to an instance of Symfony\Component\DependencyInjection\ServiceLocator',
                    $this->serviceId,
                    $this->exporter->export($actualClass)
                )
            );
        }

        if (isset($definition->getFactory()[1])) {
            $definition = $containerBuilder->findDefinition((string) $definition->getFactory()[0]);
        }

        $actualValue = $definition->getArgument(0);
        $constraint  = new IsEqual($this->expectedValue);

        if (! $constraint->evaluate($actualValue, '', true)) {
            if ($returnResult) {
                return false;
            }

            $this->fail(
                $definition,
                sprintf(
                    'The value of the service definition of "%s" (%s) is not equal to the expected ServiceLocator ref-map (%s)',
                    $this->serviceId,
                    $this->exporter->export($actualValue),
                    $this->exporter->export($this->expectedValue)
                )
            );
        }

        return true;
    }
}
