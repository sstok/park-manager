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
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\DomainName\Exception\CannotRemovePrimaryDomainName;
use ParkManager\Domain\DomainName\Exception\DomainNameNotFound;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceNotFound;
use ParkManager\Domain\Webhosting\Space\SpaceId;

/**
 * @method DomainName|null find($id, $lockMode = null, $lockVersion = null)
 */
final class DomainNameOrmRepository extends EntityRepository implements DomainNameRepository
{
    public function __construct(EntityManagerInterface $entityManager, string $className = DomainName::class)
    {
        parent::__construct($entityManager, $className);
    }

    public function get(DomainNameId $id): DomainName
    {
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
        } catch (NoResultException $e) {
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
        } catch (NoResultException $e) {
            throw DomainNameNotFound::withName($name);
        }
    }

    public function allFromOwner(?UserId $userId): iterable
    {
        if ($userId === null) {
            return $this->createQueryBuilder('d')
                ->where('d.owner IS NULL')
                ->getQuery()
                ->getResult();
        }

        return $this->createQueryBuilder('d')
            ->where('d.owner = :owner')
            ->getQuery()
            ->setParameter('owner', $userId->toString())
            ->getResult();
    }

    public function allFromSpace(SpaceId $id): iterable
    {
        return $this->createQueryBuilder('d')
            ->where('d.space = :space')
            ->getQuery()
            ->setParameter('space', $id->toString())
            ->getResult();
    }

    public function save(DomainName $domainName): void
    {
        if ($domainName->isPrimary() && $domainName->getSpace() !== null) {
            try {
                $primaryDomainName = $this->getPrimaryOf($domainName->getSpace()->getId());
            } catch (WebhostingSpaceNotFound $e) {
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
                        ->execute(['id' => $primaryDomainName->getId()]);

                    $this->_em->refresh($primaryDomainName);
                    $this->_em->persist($domainName);
                });

                return;
            }
        }

        $this->_em->persist($domainName);
    }

    public function remove(DomainName $domainName): void
    {
        if ($domainName->isPrimary() && $domainName->getSpace() !== null) {
            throw new CannotRemovePrimaryDomainName(
                $domainName->getId(),
                $domainName->getSpace()->getId()
            );
        }

        $this->_em->remove($domainName);
    }
}
