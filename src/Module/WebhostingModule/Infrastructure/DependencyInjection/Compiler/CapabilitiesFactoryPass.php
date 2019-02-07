<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Infrastructure\DependencyInjection\Compiler;

use ParkManager\Module\WebhostingModule\Infrastructure\Service\Package\CapabilitiesFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class CapabilitiesFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->has(CapabilitiesFactory::class) || ! $container->hasParameter('park_manager.webhosting.package_capabilities')) {
            return;
        }

        $capabilitiesById = [];

        foreach ($container->getParameter('park_manager.webhosting.package_capabilities') as $name => $class) {
            $capabilitiesById[$class::id()] = $class;
        }

        $managerService = $container->findDefinition(CapabilitiesFactory::class);
        $managerService->setArguments([$capabilitiesById]);
    }
}
