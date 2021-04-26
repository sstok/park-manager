<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Domain\ResultSet;
use ParkManager\Domain\Webhosting\Constraint\Exception\PlanNotFound;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Domain\Webhosting\Constraint\PlanRepository;
use ParkManager\Infrastructure\Doctrine\OrmQueryBuilderResultSet;

/**
 * @method Plan|null find($id, $lockMode = null, $lockVersion = null)
 */
class PlanOrmRepository extends EntityRepository implements PlanRepository
{
    public function __construct(EntityManagerInterface $entityManager, string $className = Plan::class)
    {
        parent::__construct($entityManager, $className);
    }

    public function all(): ResultSet
    {
        return new OrmQueryBuilderResultSet($this->createQueryBuilder('p'), 'p', false);
    }

    public function get(PlanId $id): Plan
    {
        $plan = $this->find($id->toString());

        if ($plan === null) {
            throw PlanNotFound::withId($id);
        }

        return $plan;
    }

    public function save(Plan $plan): void
    {
        $this->updateTimestamp($plan);
        $this->_em->persist($plan);
    }

    public function remove(Plan $plan): void
    {
        $this->_em->remove($plan);
    }
}
