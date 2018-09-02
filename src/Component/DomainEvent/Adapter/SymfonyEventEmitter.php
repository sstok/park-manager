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

final class SymfonyEventEmitter implements EventEmitter
{
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function emit(DomainEvent $event): DomainEvent
    {
        $this->eventDispatcher->dispatch(\get_class($event), $event);

        return $event;
    }

    /**
     * {@inheritdoc}
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->eventDispatcher->addListener($eventName, $listener, $priority);
    }

    /**
     * {@inheritdoc}
     */
    public function addSubscriber(EventSubscriber $subscriber): void
    {
        $this->eventDispatcher->addSubscriber($subscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function removeListener(string $eventName, callable $listener): void
    {
        $this->eventDispatcher->removeListener($eventName, $listener);
    }

    public function removeSubscriber(EventSubscriber $subscriber): void
    {
        $this->eventDispatcher->removeSubscriber($subscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function getListeners(string $eventName = null): array
    {
        return $this->eventDispatcher->getListeners($eventName);
    }

    /**
     * {@inheritdoc}
     */
    public function hasListeners(string $eventName = null): bool
    {
        return $this->eventDispatcher->hasListeners($eventName);
    }
}
