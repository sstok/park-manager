<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\DependencyInjection\Compiler;

use ParkManager\Bundle\WebhostingBundle\Plan\ConstraintsFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ConstraintsFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->has(ConstraintsFactory::class) || ! $container->hasParameter('park_manager.webhosting.plan_constraints')) {
            return;
        }

        $constraintsById = [];

        foreach ($container->getParameter('park_manager.webhosting.plan_constraints') as $name => $class) {
            $constraintsById[$class::id()] = $class;
        }

        $managerService = $container->findDefinition(ConstraintsFactory::class);
        $managerService->setArguments([$constraintsById]);
    }
}
