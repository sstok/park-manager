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
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Domain\Webhosting\SubDomain\Exception\SubDomainAlreadyExists;
use ParkManager\Domain\Webhosting\SubDomain\Exception\SubDomainNotFound;
use ParkManager\Domain\Webhosting\SubDomain\SubDomain;
use ParkManager\Domain\Webhosting\SubDomain\SubDomainNameId;
use ParkManager\Domain\Webhosting\SubDomain\SubDomainRepository;
use ParkManager\Infrastructure\Doctrine\OrmQueryBuilderResultSet;

/**
 * @extends EntityRepository<Subdomain>
 */
final class SubDomainOrmRepository extends EntityRepository implements SubDomainRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, SubDomain::class);
    }

    public function get(SubDomainNameId $id): SubDomain
    {
        $domainName = $this->find($id->toString());

        if ($domainName === null) {
            throw SubDomainNotFound::withId($id);
        }

        return $domainName;
    }

    public function allFromSpace(SpaceId $space): ResultSet
    {
        return new OrmQueryBuilderResultSet(
            $this->createQueryBuilder('d')->where('d.space = :space')->setParameter('space', $space->toString()),
            'd'
        );
    }

    public function save(SubDomain $subDomain): void
    {
        /** @var SubDomain|null $existing */
        $existing = $this->createQueryBuilder('d')
            ->where('d.host = :host_id AND d.name = :name')
            ->getQuery()
            ->setParameter('host_id', $subDomain->host->id->toString())
            ->setParameter('name', $subDomain->name)
            ->getOneOrNullResult()
        ;

        if ($existing !== null && ! $existing->id->equals($subDomain->id)) {
            throw new SubDomainAlreadyExists($subDomain->host->namePair, $subDomain->name, $existing->id->toString());
        }

        $this->_em->persist($subDomain);
    }

    public function remove(SubDomain $subDomain): void
    {
        $this->_em->remove($subDomain);
    }
}
