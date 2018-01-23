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

namespace ParkManager\Bridge\PhpUnit;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsEqual;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\OutOfBoundsException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class DefinitionArgumentEqualsServiceLocatorConstraint extends Constraint
{
    private $container;
    private $argumentIndex;
    private $expectedValue;

    public function __construct(ContainerBuilder $container, $argumentIndex, $expectedValue)
    {
        parent::__construct();

        $this->container = $container;
        $this->argumentIndex = (int) $argumentIndex;
        $this->expectedValue = array_map(function ($serviceId) {
            if (is_string($serviceId)) {
                return new ServiceClosureArgument(new Reference($serviceId));
            }

            if (!$serviceId instanceof ServiceClosureArgument) {
                return new ServiceClosureArgument($serviceId);
            }

            return $serviceId;
        }, $expectedValue);
    }

    public function toString()
    {
        return sprintf(
            'has an argument with index %d with the given value',
            $this->argumentIndex
        );
    }

    public function evaluate($other, $description = '', $returnResult = false)
    {
        if (!($other instanceof Definition)) {
            throw new \InvalidArgumentException(
                'Expected an instance of Symfony\Component\DependencyInjection\Definition'
            );
        }

        if (!$this->evaluateArgumentIndex($other, $returnResult)) {
            return false;
        }

        if (!$this->evaluateArgumentValue($other, $returnResult)) {
            return false;
        }

        return true;
    }

    private function evaluateArgumentIndex(Definition $definition, $returnResult)
    {
        try {
            $definition->getArgument($this->argumentIndex);
        } catch (\Exception $exception) {
            // Older versions of Symfony throw \OutOfBoundsException
            // Newer versions throw Symfony\Component\DependencyInjection\Exception\OutOfBoundsException
            if (!($exception instanceof \OutOfBoundsException || $exception instanceof OutOfBoundsException)) {
                // this was not the expected exception
                throw $exception;
            }

            if ($returnResult) {
                return false;
            }

            $this->fail(
                $definition,
                sprintf(
                    'The definition has no argument with index %d',
                    $this->argumentIndex
                )
            );
        }

        return true;
    }

    private function evaluateArgumentValue(Definition $definition, $returnResult)
    {
        $actualValue = $definition->getArgument($this->argumentIndex);

        if (!($actualValue instanceof Reference)) {
            $this->fail(
                $definition,
                sprintf(
                    'The value of argument with index %d (%s) was expected to an instance of Symfony\Component\DependencyInjection\Reference',
                    $this->argumentIndex,
                    $this->exporter->export($actualValue)
                )
            );
        }

        $serviceLocatorDef = $this->container->findDefinition((string) $actualValue);

        if (!is_a($serviceLocatorDef->getClass(), ServiceLocator::class, true)) {
            $this->fail(
                $definition,
                sprintf(
                    'The referenced service class of argument with index %d (%s) was expected to an instance of Symfony\Component\DependencyInjection\ServiceLocator',
                    $this->argumentIndex,
                    $this->exporter->export($serviceLocatorDef->getClass())
                )
            );
        }

        if (isset($serviceLocatorDef->getFactory()[1])) {
            $serviceLocatorDef = $this->container->findDefinition((string) $serviceLocatorDef->getFactory()[0]);
        }

        $actualValue = $serviceLocatorDef->getArgument(0);
        $constraint = new IsEqual($this->expectedValue);

        if (!$constraint->evaluate($actualValue, '', true)) {
            if ($returnResult) {
                return false;
            }

            $this->fail(
                $definition,
                sprintf(
                    'The value of argument with index %d (%s) is not equal to the expected ServiceLocator ref-map (%s)',
                    $this->argumentIndex,
                    $this->exporter->export($actualValue),
                    $this->exporter->export($this->expectedValue)
                )
            );
        }

        return true;
    }
}
