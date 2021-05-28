<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository as BaseEntityRepository;

/**
 * @template T of object
 * @template-extends BaseEntityRepository<T>
 *
 * @method ?T find($id, $lockMode = null, $lockVersion = null)
 * @method ?T findOneBy(array $criteria, array $orderBy = null)
 */
abstract class EntityRepository extends BaseEntityRepository
{
    /**
     * @param class-string<T> $className The class name of the entity this repository manages
     */
    public function __construct(EntityManagerInterface $entityManager, string $className)
    {
        parent::__construct($entityManager, $entityManager->getMetadataFactory()->getMetadataFor($className));
    }

    /**
     * @param T $entity
     */
    protected function updateTimestamp(object $entity): void
    {
        (function (): void {
            $this->__updateTimestamp();
        })->call($entity);
    }
}
