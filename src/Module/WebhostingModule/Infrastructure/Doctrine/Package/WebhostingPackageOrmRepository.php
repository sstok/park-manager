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

namespace ParkManager\Module\WebhostingModule\Infrastructure\Doctrine\Package;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Component\DomainEvent\EventEmitter;
use ParkManager\Module\CoreModule\Infrastructure\Doctrine\EventSourcedEntityRepository;
use ParkManager\Module\WebhostingModule\Domain\Package\Exception\WebhostingPackageNotFound;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackage;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackageId;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackageRepository;

/**
 * @method WebhostingPackage|null find($id, $lockMode = null, $lockVersion = null)
 */
final class WebhostingPackageOrmRepository extends EventSourcedEntityRepository implements WebhostingPackageRepository
{
    public function __construct(EntityManagerInterface $entityManager, EventEmitter $eventEmitter, string $className = WebhostingPackage::class)
    {
        parent::__construct($entityManager, $eventEmitter, $className);
    }

    public function get(WebhostingPackageId $id): WebhostingPackage
    {
        $package = $this->find($id->toString());

        if ($package === null) {
            throw WebhostingPackageNotFound::withId($id);
        }

        return $package;
    }

    public function save(WebhostingPackage $package): void
    {
        $this->_em->persist($package);
        $this->doDispatchEvents($package);
    }

    public function remove(WebhostingPackage $package): void
    {
        $this->_em->remove($package);
        $this->doDispatchEvents($package);
    }
}
