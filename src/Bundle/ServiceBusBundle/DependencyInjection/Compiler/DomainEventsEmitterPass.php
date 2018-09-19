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

namespace ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use function sprintf;

final class DomainEventsEmitterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('park_manager.service_bus.domain_event_emitter', true) as $serviceId => $tags) {
            if (! isset($tags[0]['bus-id'])) {
                throw new \InvalidArgumentException(sprintf('Service %s is missing the bus-id attribute.', $serviceId));
            }

            $serviceId = (string) $container->findDefinition($serviceId)->getArgument(0);
            $busId     = $tags[0]['bus-id'];

            (new RegisterListenersPass(
                $serviceId,
                $busId . '.domain_event_listener',
                $busId . '.domain_event_subscriber'
            ))->process($container);
        }
    }
}
