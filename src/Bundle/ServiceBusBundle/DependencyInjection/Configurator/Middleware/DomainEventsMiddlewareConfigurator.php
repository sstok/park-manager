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

namespace ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\Middleware;

use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\AdvancedMiddlewareConfigurator;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\MiddlewaresConfigurator;
use ParkManager\Component\DomainEvent\Adapter\SymfonyEventEmitter;
use ParkManager\Component\DomainEvent\EventSubscriber;
use Symfony\Component\DependencyInjection\Loader\Configurator\AbstractServiceConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class DomainEventsMiddlewareConfigurator implements AdvancedMiddlewareConfigurator
{
    private $parent;
    private $serviceId;
    private $di;

    public function __construct(MiddlewaresConfigurator $parent, AbstractServiceConfigurator $di, string $serviceId)
    {
        $this->parent = $parent;
        $this->serviceId = $serviceId;
        $this->di = $di;

        // Inner Symfony dispatcher. Needs to be accessible for decorating by
        $di->set($serviceId.'.domain_event_emitter.symfony', EventDispatcher::class)
            ->autoconfigure(false)
            ->autowire(false)
            ->private();

        $di->set($serviceId.'.domain_event_emitter', SymfonyEventEmitter::class)
            ->tag('park_manager.service_bus.domain_event_emitter', ['bus-id' => $serviceId])
            ->args([new Reference($serviceId.'.domain_event_emitter.symfony')])
            ->autoconfigure(false)
            ->autowire(false)
            ->private();
    }

    public function listener(string $class, array $events, array $arguments = []): self
    {
        $service = $this->di->set($this->serviceId.'.domain_event_listener.'.$class, $class)->private();
        $service->args($arguments);

        foreach ($events as $event) {
            if (!isset($event['event'])) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Domain event listener "%s" for %s must define the "event" attribute.',
                        $class,
                        $this->serviceId
                    )
                );
            }

            $service->tag($this->serviceId.'.domain_event_listener', $event);
        }

        return $this;
    }

    public function subscriber(string $class, array $arguments = []): self
    {
        $interface = EventSubscriber::class;

        if (!is_subclass_of($class, $interface)) {
            if (!class_exists($class, false)) {
                throw new \InvalidArgumentException(
                    sprintf('EventSubscriber "%s" for service "%s" cannot be found.', $class, $this->serviceId)
                );
            }

            throw new \InvalidArgumentException(sprintf('Class "%s" must implement interface "%s".', $class, $interface));
        }

        $this->di->set($this->serviceId.'.domain_event_listener.'.$class, $class)
            ->tag($this->serviceId.'.domain_event_subscriber')
            ->args($arguments)
            ->private();

        return $this;
    }

    public function end(): MiddlewaresConfigurator
    {
        return $this->parent;
    }
}
