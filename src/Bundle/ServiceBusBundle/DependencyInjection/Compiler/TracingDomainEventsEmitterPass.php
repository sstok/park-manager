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

namespace ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher;

final class TracingDomainEventsEmitterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('park_manager.service_bus.domain_event_emitter', true) as $serviceId => $tags) {
            $serviceId = $container->findDefinition($serviceId)->getArgument(0);
            $container->register($serviceId.'.debug', TraceableEventDispatcher::class)
                ->setDecoratedService($serviceId)
                ->setArguments([
                    new Reference($serviceId.'.debug.inner'),
                    new Reference('debug.stopwatch'),
                    new Reference('monolog.logger.domain_event', ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                ]);
        }
    }
}
