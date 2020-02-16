<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\DependencyInjection\Compiler;

use ParkManager\Infrastructure\Security\PermissionAccessManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class PermissionDeciderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $def = $container->findDefinition(PermissionAccessManager::class);
        $collected = [];

        foreach ($container->findTaggedServiceIds('park_manager.security.permission_decider') as $serviceId => $tags) {
            $className = substr($container->getDefinition($serviceId)->getClass(), 0, -7);

            $collected[$className] = new Reference($serviceId);
        }

        $def->setArgument(1, ServiceLocatorTagPass::register($container, $collected, PermissionAccessManager::class));
    }
}
