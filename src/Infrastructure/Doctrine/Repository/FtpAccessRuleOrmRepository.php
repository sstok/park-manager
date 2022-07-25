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
use ParkManager\Domain\Webhosting\Ftp\AccessRule;
use ParkManager\Domain\Webhosting\Ftp\AccessRuleId;
use ParkManager\Domain\Webhosting\Ftp\AccessRuleRepository;
use ParkManager\Domain\Webhosting\Ftp\AccessRuleStrategy;
use ParkManager\Domain\Webhosting\Ftp\Exception\AccessRuleNotFound;
use ParkManager\Domain\Webhosting\Ftp\FtpUserId;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Infrastructure\Doctrine\OrmQueryBuilderResultSet;

/**
 * @extends EntityRepository<AccessRule>
 */
final class FtpAccessRuleOrmRepository extends EntityRepository implements AccessRuleRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, AccessRule::class);
    }

    public function get(AccessRuleId $id): AccessRule
    {
        $rule = $this->find($id->toString());

        if ($rule === null) {
            throw AccessRuleNotFound::withId($id);
        }

        return $rule;
    }

    public function hasAnyAllow(SpaceId | FtpUserId $id): bool
    {
        if ($id instanceof SpaceId) {
            $query = $this->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.space = :space AND r.user IS NULL AND r.strategy = :strategy')
                ->setMaxResults(1)
                ->getQuery()
                ->setParameter('space', $id->toString())
                ->setParameter('strategy', AccessRuleStrategy::ALLOW)
            ;

            return ((int) $query->getSingleScalarResult()) > 0;
        }

        $query = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.user = :user AND r.strategy = :strategy')
            ->setMaxResults(1)
            ->getQuery()
            ->setParameter('user', $id->toString())
            ->setParameter('strategy', AccessRuleStrategy::ALLOW)
        ;

        return ((int) $query->getSingleScalarResult()) > 0;
    }

    public function allOfSpace(SpaceId $space): ResultSet
    {
        return new OrmQueryBuilderResultSet(
            $this->createQueryBuilder('r')
                ->where('r.space = :space')
                ->setParameter('space', $space->toString()),
            'r'
        );
    }

    public function allOfUser(FtpUserId $id): ResultSet
    {
        return new OrmQueryBuilderResultSet(
            $this->createQueryBuilder('r')
                ->join('r.user', 'ru')
                ->where('ru.id = :user')
                ->setParameter('user', $id->toString()),
            'r'
        );
    }

    public function save(AccessRule $rule): void
    {
        $this->_em->persist($rule);
    }

    public function remove(AccessRule $rule): void
    {
        $this->_em->remove($rule);
    }
}
