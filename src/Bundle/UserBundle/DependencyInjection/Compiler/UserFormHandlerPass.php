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

namespace ParkManager\Bundle\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class UserFormHandlerPass implements CompilerPassInterface
{
    private $registryId;
    private $tagName;

    public function __construct(string $registryId, string $tagName)
    {
        $this->registryId = $registryId;
        $this->tagName = $tagName;
    }

    public function process(ContainerBuilder $container): void
    {
        $registryMap = [];

        // Builds an array with fully-qualified type class names as keys and service IDs as values
        foreach ($container->findTaggedServiceIds($this->tagName, true) as $serviceId => $tag) {
            $registryMap[$container->findDefinition($serviceId)->getClass()] = new Reference($serviceId);
        }

        $container->findDefinition($this->registryId)
            ->replaceArgument(0, ServiceLocatorTagPass::register($container, $registryMap));
    }
}
