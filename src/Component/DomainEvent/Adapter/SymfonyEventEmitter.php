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

namespace ParkManager\Component\DomainEvent\Adapter;

use ParkManager\Component\DomainEvent\DomainEvent;
use ParkManager\Component\DomainEvent\EventEmitter;
use ParkManager\Component\DomainEvent\EventSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function get_class;

final class SymfonyEventEmitter implements EventEmitter
{
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function emit(DomainEvent $event): DomainEvent
    {
        $this->eventDispatcher->dispatch(get_class($event), $event);

        return $event;
    }

    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->eventDispatcher->addListener($eventName, $listener, $priority);
    }

    public function addSubscriber(EventSubscriber $subscriber): void
    {
        $this->eventDispatcher->addSubscriber($subscriber);
    }

    public function removeListener(string $eventName, callable $listener): void
    {
        $this->eventDispatcher->removeListener($eventName, $listener);
    }

    public function removeSubscriber(EventSubscriber $subscriber): void
    {
        $this->eventDispatcher->removeSubscriber($subscriber);
    }

    public function getListeners(?string $eventName = null): array
    {
        return $this->eventDispatcher->getListeners($eventName);
    }

    public function hasListeners(?string $eventName = null): bool
    {
        return $this->eventDispatcher->hasListeners($eventName);
    }
}
