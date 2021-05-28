<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use ParkManager\Domain\Organization\Exception\OrganizationNotFound;
use ParkManager\Domain\Organization\Organization;
use ParkManager\Domain\Organization\OrganizationId;
use ParkManager\Domain\Organization\OrganizationRepository;
use ParkManager\Domain\ResultSet;
use ParkManager\Domain\User\UserId;
use ParkManager\Infrastructure\Doctrine\OrmQueryBuilderResultSet;

/**
 * @extends EntityRepository<Organization>
 */
class OrganizationOrmRepository extends EntityRepository implements OrganizationRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, Organization::class);
    }

    public function get(OrganizationId $id): Organization
    {
        $organization = $this->find($id->toString());

        if ($organization === null) {
            throw OrganizationNotFound::withId($id);
        }

        return $organization;
    }

    public function save(Organization $organization): void
    {
        $this->updateTimestamp($organization);
        $this->_em->persist($organization);
    }

    public function remove(Organization $organization): void
    {
        $this->_em->remove($organization);
    }

    public function all(): ResultSet
    {
        return new OrmQueryBuilderResultSet($this->createQueryBuilder('o'), rootAlias: 'o', fetchJoinCollection: true);
    }

    public function allAccessibleBy(UserId $userId): ResultSet
    {
        $queryBuilder = $this->createQueryBuilder('o')
            ->join('o.members', 'om', Join::WITH, 'om.user = :user')
            ->setParameter('user', $userId->toString())
        ;

        return new OrmQueryBuilderResultSet($queryBuilder, rootAlias: 'o', fetchJoinCollection: true);
    }
}
