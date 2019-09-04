<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Doctrine\DomainName;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use ParkManager\Bundle\CoreBundle\Doctrine\EntityRepository;
use ParkManager\Bundle\WebhostingBundle\Model\Account\Exception\WebhostingAccountNotFound;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountId;
use ParkManager\Bundle\WebhostingBundle\Model\DomainName;
use ParkManager\Bundle\WebhostingBundle\Model\DomainName\Exception\WebhostingDomainNameNotFound;
use ParkManager\Bundle\WebhostingBundle\Model\DomainName\WebhostingDomainName;
use ParkManager\Bundle\WebhostingBundle\Model\DomainName\WebhostingDomainNameId;
use ParkManager\Bundle\WebhostingBundle\Model\DomainName\WebhostingDomainNameRepository;

/**
 * @method WebhostingDomainName|null find($id, $lockMode = null, $lockVersion = null)
 */
class WebhostingDomainNameOrmRepository extends EntityRepository implements WebhostingDomainNameRepository
{
    public function __construct(EntityManagerInterface $entityManager, string $className = WebhostingDomainName::class)
    {
        parent::__construct($entityManager, $className);
    }

    public function get(WebhostingDomainNameId $id): WebhostingDomainName
    {
        $domainName = $this->find($id->toString());

        if ($domainName === null) {
            throw WebhostingDomainNameNotFound::withId($id);
        }

        return $domainName;
    }

    public function save(WebhostingDomainName $domainName): void
    {
        if ($domainName->isPrimary()) {
            try {
                $primaryDomainName = $this->getPrimaryOf($domainName->getAccount()->getId());
            } catch (WebhostingAccountNotFound $e) {
                $primaryDomainName = $domainName;
            }

            // If there is a primary marking for another DomainName (within in this account)
            // remove the primary marking for that DomainName.
            if ($primaryDomainName !== $domainName) {
                $this->_em->transactional(function () use ($domainName, $primaryDomainName): void {
                    // There is no setter function for the Model as this is an implementation detail.
                    $this->_em->createQueryBuilder()
                        ->update($this->_entityName, 'd')
                        ->set('d.primary', 'false')
                        ->where('d.id = :id')
                        ->getQuery()
                        ->execute(['id' => $primaryDomainName->getId()])
                    ;

                    $this->_em->refresh($primaryDomainName);
                    $this->_em->persist($domainName);
                });

                return;
            }
        }

        $this->_em->persist($domainName);
    }

    public function remove(WebhostingDomainName $domainName): void
    {
        if ($domainName->isPrimary()) {
            throw DomainName\Exception\CannotRemovePrimaryDomainName::of(
                $domainName->getId(),
                $domainName->getAccount()->getId()
            );
        }

        $this->_em->remove($domainName);
    }

    public function getPrimaryOf(WebhostingAccountId $id): WebhostingDomainName
    {
        try {
            return $this->createQueryBuilder('d')
                ->where('d.account = :id AND d.primary = true')
                ->getQuery()
                ->setParameters(['id' => $id->toString()])
                ->getSingleResult()
            ;
        } catch (NoResultException $e) {
            throw WebhostingAccountNotFound::withId($id);
        }
    }

    public function findByFullName(DomainName $name): ?WebhostingDomainName
    {
        return $this->createQueryBuilder('d')
            ->where('d.domainName.name = :name AND d.domainName.tld = :tld')
            ->getQuery()
            ->setParameters(['name' => $name->name, 'tld' => $name->tld])
            ->getOneOrNullResult()
        ;
    }
}
