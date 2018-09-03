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
use ParkManager\Module\WebhostingModule\Domain\Package\{
    WebhostingPackage,
    WebhostingPackageId,
    WebhostingPackageRepository
};

final class WebhostingPackageOrmRepository extends EventSourcedEntityRepository implements WebhostingPackageRepository
{
    public function __construct(EntityManagerInterface $entityManager, EventEmitter $eventEmitter, string $className = WebhostingPackage::class)
    {
        parent::__construct($entityManager, $eventEmitter, $className);
    }

    public function get(WebhostingPackageId $id): WebhostingPackage
    {
        /** @var WebhostingPackage|null $package */
        $package = $this->find($id->toString());

        if (null === $package) {
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
