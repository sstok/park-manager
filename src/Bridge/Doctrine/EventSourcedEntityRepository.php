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

namespace ParkManager\Bridge\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use ParkManager\Component\Model\EventsRecordingAggregateRoot;
use ParkManager\Component\Model\Util\EventsExtractor;
use Prooph\ServiceBus\EventBus;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
abstract class EventSourcedEntityRepository extends EntityRepository
{
    protected $eventBus;

    public function __construct(EntityManagerInterface $entityManager, EventBus $eventBus, string $className)
    {
        $this->_em = $entityManager;
        $this->_class = $entityManager->getClassMetadata($className);
        $this->_entityName = $className;
        $this->eventBus = $eventBus;
    }

    public function getModelClass(): string
    {
        return $this->_entityName;
    }

    protected function doTransactionalPersist($entity): void
    {
        $this->_em->transactional(function () use ($entity) {
            $this->_em->persist($entity);
        });
    }

    protected function doTransactionalRemove($entity): void
    {
        $this->_em->transactional(function () use ($entity) {
            $this->_em->remove($entity);
        });
    }

    protected function doDispatchEvents(EventsRecordingAggregateRoot $aggregateRoot): void
    {
        foreach (EventsExtractor::newInstance()->extractDomainEvents($aggregateRoot) as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
