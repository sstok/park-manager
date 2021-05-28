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
use ParkManager\Domain\Webhosting\Email\Exception\MailboxNotFound;
use ParkManager\Domain\Webhosting\Email\Mailbox;
use ParkManager\Domain\Webhosting\Email\MailboxId;
use ParkManager\Domain\Webhosting\Email\MailboxRepository;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Infrastructure\Doctrine\OrmQueryBuilderResultSet;

/**
 * @extends EntityRepository<Mailbox>
 */
final class MailboxOrmRepository extends EntityRepository implements MailboxRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, Mailbox::class);
    }

    public function get(MailboxId $id): Mailbox
    {
        $mailbox = $this->find($id->toString());

        if ($mailbox === null) {
            throw MailboxNotFound::withId($id);
        }

        return $mailbox;
    }

    public function getByName(string $address, DomainNamePair $domainNamePair): Mailbox
    {
        try {
            return $this->createQueryBuilder('m')
                ->join(DomainName::class, 'd')
                ->where('m.address = :address AND d.namePair.name = :domain_name AND d.namePair.tld = :domain_tld')
                ->getQuery()
                ->setParameter('address', $address)
                ->setParameter('domain_name', $domainNamePair->name)
                ->setParameter('domain_tld', $domainNamePair->tld)
                ->getSingleResult()
            ;
        } catch (NoResultException) {
            throw MailboxNotFound::withName($address . '@' . $domainNamePair->toString());
        }
    }

    public function allBySpace(SpaceId $space): ResultSet
    {
        return new OrmQueryBuilderResultSet(
            $this->createQueryBuilder('m')
                ->where('m.space = :space')
                ->setParameter('space', $space->toString()),
            'm'
        );
    }

    public function countBySpace(SpaceId $space): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.space = :space')
            ->getQuery()
            ->setParameter('space', $space->toString())
            ->getSingleScalarResult()
        ;
    }

    public function save(Mailbox $mailbox): void
    {
        $this->updateTimestamp($mailbox);
        $this->_em->persist($mailbox);
    }

    public function remove(Mailbox $mailbox): void
    {
        $this->_em->remove($mailbox);
    }
}
