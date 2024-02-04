<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Repository;

use Lifthill\Bridge\Doctrine\Repository\EntityRepository as BaseEntityRepository;

/**
 * @template T of object
 *
 * @template-extends BaseEntityRepository<T>
 */
abstract class EntityRepository extends BaseEntityRepository
{
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
