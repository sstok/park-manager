<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Domain\Exception\OwnerNotFound;
use ParkManager\Domain\Owner;
use ParkManager\Domain\OwnerId;
use ParkManager\Domain\OwnerRepository;

/**
 * @extends EntityRepository<Owner>
 */
final class OwnerOrmRepository extends EntityRepository implements OwnerRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, Owner::class);
    }

    public function get(OwnerId $id): Owner
    {
        $owner = $this->find($id->toString());

        if ($owner === null) {
            throw OwnerNotFound::withId($id);
        }

        return $owner;
    }

    public function save(Owner $owner): void
    {
        $this->_em->persist($owner);
    }
}
