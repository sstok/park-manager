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
use Doctrine\ORM\Query\Expr\Join;
use Lifthill\Bridge\Doctrine\OrmQueryBuilderResultSet;
use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use Lifthill\Component\Common\Domain\ResultSet;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\DomainName\Exception\CannotRemovePrimaryDomainName;
use ParkManager\Domain\DomainName\Exception\DomainNameNotFound;
use ParkManager\Domain\OwnerId;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceNotFound;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;

/**
 * @extends EntityRepository<DomainName>
 */
final class DomainNameOrmRepository extends EntityRepository implements DomainNameRepository
{
    public function __construct(EntityManagerInterface $entityManager, string $className = DomainName::class)
    {
        parent::__construct($entityManager, $className);
    }

    public function get(DomainNameId | DomainNamePair $id): DomainName
    {
        if ($id instanceof DomainNamePair) {
            return $this->getByName($id);
        }

        $domainName = $this->find($id->toString());

        if ($domainName === null) {
            throw DomainNameNotFound::withId($id);
        }

        return $domainName;
    }

    public function getPrimaryOf(SpaceId $id): DomainName
    {
        try {
            return $this->createQueryBuilder('d')
                ->where('d.space = :id AND d.primary = true')
                ->getQuery()
                ->setParameter('id', $id->toString())
                ->getSingleResult();
        } catch (NoResultException) {
            throw WebhostingSpaceNotFound::withId($id);
        }
    }

    public function getByName(DomainNamePair $name): DomainName
    {
        try {
            return $this->createQueryBuilder('d')
                ->where('d.namePair.name = :name AND d.namePair.tld = :tld')
                ->getQuery()
                ->setParameter('name', $name->name)
                ->setParameter('tld', $name->tld)
                ->getSingleResult();
        } catch (NoResultException) {
            throw DomainNameNotFound::withName($name);
        }
    }

    public function allFromOwner(OwnerId $id): ResultSet
    {
        return new OrmQueryBuilderResultSet(
            $this->createQueryBuilder('d')
                ->where('d.owner = :owner')
                ->setParameter('owner', $id->toString()),
            'd'
        );
    }

    public function all(): ResultSet
    {
        return new OrmQueryBuilderResultSet($this->createQueryBuilder('d'), rootAlias: 'd', fetchJoinCollection: true);
    }

    public function allAccessibleBy(OwnerId $ownerId): ResultSet
    {
        return new OrmQueryBuilderResultSet(
            $this->createQueryBuilder('d')
                ->leftJoin('d.space', 's')
                ->where('d.owner = :owner OR s.owner = :owner')
                ->setParameter('owner', $ownerId->toString()),
            'd',
            true
        );
    }

    public function allFromSpace(SpaceId $id): ResultSet
    {
        return new OrmQueryBuilderResultSet(
            $this->createQueryBuilder('d')
                ->join(Space::class, 's', Join::WITH, 'd.space = s.id')
                ->where('s.id = :space')
                ->setParameter('space', $id->toString()),
            'd',
            true
        );
    }

    public function save(DomainName $domainName): void
    {
        if ($domainName->primary && $domainName->space !== null) {
            try {
                $primaryDomainName = $this->getPrimaryOf($domainName->space->id);
            } catch (WebhostingSpaceNotFound) {
                $primaryDomainName = $domainName;
            }

            // If there is a primary marking for another DomainName (within in this space)
            // remove the primary marking for that DomainName.
            if ($primaryDomainName !== $domainName) {
                $this->_em->transactional(function () use ($domainName, $primaryDomainName): void {
                    // There is no setter function for the Model as this is an implementation detail.
                    $this->_em->createQueryBuilder()
                        ->update($this->_entityName, 'd')
                        ->set('d.primary', 'false')
                        ->where('d.id = :id')
                        ->getQuery()
                        ->setParameter('id', $primaryDomainName->id)
                        ->execute();

                    $this->_em->refresh($primaryDomainName);

                    $this->updateTimestamp($domainName);
                    $this->_em->persist($domainName);
                });

                return;
            }
        }

        $this->updateTimestamp($domainName);
        $this->_em->persist($domainName);
    }

    public function remove(DomainName $domainName): void
    {
        if ($domainName->primary && $domainName->space !== null) {
            throw new CannotRemovePrimaryDomainName(
                $domainName->id,
                $domainName->space->id
            );
        }

        $this->_em->remove($domainName);
    }
}
