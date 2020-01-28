<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Domain\Webhosting\Constraint\ConstraintSetId;
use ParkManager\Domain\Webhosting\Constraint\Exception\ConstraintSetNotFound;
use ParkManager\Domain\Webhosting\Constraint\SharedConstraintSet;
use ParkManager\Domain\Webhosting\Constraint\SharedConstraintSetRepository;

/**
 * @method SharedConstraintSet|null find($id, $lockMode = null, $lockVersion = null)
 */
class SharedConstraintSetOrmRepository extends EntityRepository implements SharedConstraintSetRepository
{
    public function __construct(EntityManagerInterface $entityManager, string $className = SharedConstraintSet::class)
    {
        parent::__construct($entityManager, $className);
    }

    public function get(ConstraintSetId $id): SharedConstraintSet
    {
        $constraintSet = $this->find($id->toString());

        if ($constraintSet === null) {
            throw ConstraintSetNotFound::withId($id);
        }

        return $constraintSet;
    }

    public function save(SharedConstraintSet $constraintSet): void
    {
        $this->_em->persist($constraintSet);
    }

    public function remove(SharedConstraintSet $constraintSet): void
    {
        $this->_em->remove($constraintSet);
    }
}
