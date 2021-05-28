<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\ResultSet;
use ParkManager\Domain\Webhosting\Email\Exception\EmailForwardNotFound;
use ParkManager\Domain\Webhosting\Email\Forward;
use ParkManager\Domain\Webhosting\Email\ForwardId;
use ParkManager\Domain\Webhosting\Email\ForwardRepository;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Infrastructure\Doctrine\OrmQueryBuilderResultSet;

/**
 * @extends EntityRepository<Forward>
 */
final class EmailForwardOrmRepository extends EntityRepository implements ForwardRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, Forward::class);
    }

    public function get(ForwardId $id): Forward
    {
        $mailbox = $this->find($id->toString());

        if ($mailbox === null) {
            throw EmailForwardNotFound::withId($id);
        }

        return $mailbox;
    }

    public function getByName(string $address, DomainNamePair $domainNamePair): Forward
    {
        try {
            return $this->createQueryBuilder('f')
                ->join(DomainName::class, 'd')
                ->where('f.address = :address AND d.namePair.name = :domain_name AND d.namePair.tld = :domain_tld')
                ->getQuery()
                ->setParameter('address', $address)
                ->setParameter('domain_name', $domainNamePair->name)
                ->setParameter('domain_tld', $domainNamePair->tld)
                ->getSingleResult()
            ;
        } catch (NoResultException) {
            throw EmailForwardNotFound::withName($address . '@' . $domainNamePair->toString());
        }
    }

    public function allBySpace(SpaceId $space): ResultSet
    {
        return new OrmQueryBuilderResultSet(
            $this->createQueryBuilder('f')
                ->where('f.space = :space')
                ->setParameter('space', $space->toString()),
            'f'
        );
    }

    public function countBySpace(SpaceId $space): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.space = :space')
            ->getQuery()
            ->setParameter('space', $space->toString())
            ->getSingleScalarResult()
        ;
    }

    public function save(Forward $forward): void
    {
        $this->_em->persist($forward);
    }

    public function remove(Forward $forward): void
    {
        $this->_em->remove($forward);
    }
}
