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

namespace ParkManager\Bridge\ServiceBus\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class DomainEventsEmitterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('park_manager.service_bus.domain_event_emitter', true) as $serviceId => $tags) {
            if (!isset($tags[0]['bus-id'])) {
                throw new \InvalidArgumentException(sprintf('Service %s is missing the bus-id attribute.', $serviceId));
            }

            $serviceId = (string) $container->findDefinition($serviceId)->getArgument(0);
            $busId = $tags[0]['bus-id'];

            (new RegisterListenersPass(
                $serviceId,
                $busId.'.domain_event_listener',
                $busId.'.domain_event_subscriber'
            ))->process($container);
        }
    }
}
