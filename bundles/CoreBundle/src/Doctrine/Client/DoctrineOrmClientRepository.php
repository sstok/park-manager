<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Doctrine\Client;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Bundle\CoreBundle\Doctrine\EntityRepository;
use ParkManager\Bundle\CoreBundle\Model\Client\Client;
use ParkManager\Bundle\CoreBundle\Model\Client\ClientId;
use ParkManager\Bundle\CoreBundle\Model\Client\ClientRepository;
use ParkManager\Bundle\CoreBundle\Model\Client\Exception\ClientNotFound;
use ParkManager\Bundle\CoreBundle\Model\Client\Exception\EmailChangeConfirmationRejected;
use ParkManager\Bundle\CoreBundle\Model\EmailAddress;
use ParkManager\Bundle\CoreBundle\Model\Exception\PasswordResetTokenNotAccepted;
use ParkManager\Bundle\CoreBundle\Security\AuthenticationFinder;
use ParkManager\Bundle\CoreBundle\Security\SecurityUser;

/**
 * @method Client find($id, $lockMode = null, $lockVersion = null)
 * @method Client findOneBy(array $criteria, array $orderBy = null)
 */
class DoctrineOrmClientRepository extends EntityRepository implements ClientRepository, AuthenticationFinder
{
    public function __construct(EntityManagerInterface $entityManager, string $className = Client::class)
    {
        parent::__construct($entityManager, $className);
    }

    public function get(ClientId $id): Client
    {
        $user = $this->find($id);

        if ($user === null) {
            throw ClientNotFound::withId($id);
        }

        return $user;
    }

    public function save(Client $user): void
    {
        $this->_em->persist($user);
    }

    public function remove(Client $user): void
    {
        $this->_em->remove($user);
    }

    public function getByEmail(EmailAddress $email): Client
    {
        $client = $this->createQueryBuilder('u')
            ->where('u.email.canonical = :email')
            ->getQuery()
            ->setParameter('email', $email->canonical)
            ->getOneOrNullResult()
        ;

        if ($client === null) {
            throw ClientNotFound::withEmail($email);
        }

        return $client;
    }

    public function getByEmailAddressChangeToken(string $selector): Client
    {
        $client = $this->createQueryBuilder('u')
            ->where('u.emailAddressChangeToken.selector = :selector')
            ->getQuery()
            ->setParameter('selector', $selector)
            ->getOneOrNullResult()
        ;

        if ($client === null) {
            throw new EmailChangeConfirmationRejected();
        }

        return $client;
    }

    public function getByPasswordResetToken(string $selector): Client
    {
        $client = $this->createQueryBuilder('u')
            ->where('u.passwordResetToken.selector = :selector')
            ->getQuery()
            ->setParameter('selector', $selector)
            ->getOneOrNullResult()
        ;

        if ($client === null) {
            throw new PasswordResetTokenNotAccepted();
        }

        return $client;
    }

    public function findAuthenticationByEmail(string $email): ?SecurityUser
    {
        /** @var Client $client */
        $client = $this->createQueryBuilder('u')
            ->where('u.email.canonical = :email')
            ->getQuery()
            ->setParameter('email', $email)
            ->getOneOrNullResult()
        ;

        if ($client !== null) {
            return $client->toSecurityUser();
        }

        return null;
    }

    public function findAuthenticationById(string $id): ?SecurityUser
    {
        /** @var Client $client */
        $client = $this->createQueryBuilder('u')
            ->where('u.id = :id')
            ->getQuery()
            ->setParameter('id', $id)
            ->getOneOrNullResult()
        ;

        if ($client !== null) {
            return $client->toSecurityUser();
        }

        return null;
    }
}
