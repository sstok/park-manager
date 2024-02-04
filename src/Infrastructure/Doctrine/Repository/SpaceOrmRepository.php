<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Lifthill\Bridge\Doctrine\OrmQueryBuilderResultSet;
use Lifthill\Component\Common\Domain\ResultSet;
use ParkManager\Domain\OwnerId;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Domain\Webhosting\Space\Exception\CannotRemoveActiveWebhostingSpace;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceNotFound;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;

/**
 * @extends EntityRepository<Space>
 */
class SpaceOrmRepository extends EntityRepository implements SpaceRepository
{
    public function __construct(EntityManagerInterface $entityManager, string $className = Space::class)
    {
        parent::__construct($entityManager, $className);
    }

    public function get(SpaceId $id): Space
    {
        $space = $this->find($id->toString());

        if ($space === null) {
            throw WebhostingSpaceNotFound::withId($id);
        }

        return $space;
    }

    public function all(): ResultSet
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->select(['s', 'p'])
            ->leftJoin('s.plan', 'p');

        return new OrmQueryBuilderResultSet($queryBuilder, 's', fetchJoinCollection: true);
    }

    public function allWithAssignedPlan(PlanId $id): ResultSet
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->andWhere('s.plan = :id')
            ->setParameter('id', $id->toString());

        return new OrmQueryBuilderResultSet($queryBuilder, 's');
    }

    public function allFromOwner(OwnerId $id): ResultSet
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->andWhere('s.owner = :id')
            ->setParameter('id', $id->toString());

        return new OrmQueryBuilderResultSet($queryBuilder, 's');
    }

    public function save(Space $space): void
    {
        $this->updateTimestamp($space);
        $this->_em->persist($space);
    }

    public function remove(Space $space): void
    {
        if (! $space->isMarkedForRemoval()) {
            throw CannotRemoveActiveWebhostingSpace::withId($space->id);
        }

        $this->_em->remove($space);
    }
}
