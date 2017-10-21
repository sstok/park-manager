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

namespace ParkManager\Module\Webhosting\Infrastructure\Symfony\DependencyInjection\Compiler;

use ParkManager\Module\Webhosting\Model\Package\CommandSubscribingCapability;
use ParkManager\Module\Webhosting\Service\Package\CommandToCapabilitiesGuard;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class CommandToCapabilitiesGuardPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(CommandToCapabilitiesGuard::class)) {
            return;
        }

        $parameters = $container->getParameterBag();
        $mappingService = $container->findDefinition(CommandToCapabilitiesGuard::class);
        $mappings = $mappingService->getArgument(1);

        foreach ($container->findTaggedServiceIds('park_manager.webhosting_capability') as $serviceId => $t) {
            $class = $parameters->resolveValue($container->findDefinition($serviceId)->getClass());

            if (is_a($class, CommandSubscribingCapability::class, true)) {
                foreach ($class::subscribedCommands() as $command) {
                    $mappings[$command][] = $class;
                }
            }
        }

        foreach ($mappings as $capability => $commands) {
            $mappings[$capability] = array_unique($commands);
        }

        $mappingService->replaceArgument(1, $mappings);
    }
}
