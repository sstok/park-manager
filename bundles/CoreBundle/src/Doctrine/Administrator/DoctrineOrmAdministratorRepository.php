<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Doctrine\Administrator;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Bundle\CoreBundle\Doctrine\EventSourcedEntityRepository;
use ParkManager\Bundle\CoreBundle\Model\Administrator\Administrator;
use ParkManager\Bundle\CoreBundle\Model\Administrator\AdministratorId;
use ParkManager\Bundle\CoreBundle\Model\Administrator\AdministratorRepository;
use ParkManager\Bundle\CoreBundle\Model\Administrator\Exception\AdministratorNotFound;
use ParkManager\Bundle\CoreBundle\Model\EmailAddress;
use ParkManager\Bundle\CoreBundle\Model\Exception\PasswordResetTokenNotAccepted;
use ParkManager\Bundle\CoreBundle\Security\AuthenticationFinder;
use ParkManager\Bundle\CoreBundle\Security\SecurityUser;
use Symfony\Component\Messenger\MessageBusInterface as MessageBus;

/**
 * @method Administrator find($id, $lockMode = null, $lockVersion = null)
 */
class DoctrineOrmAdministratorRepository extends EventSourcedEntityRepository implements AdministratorRepository, AuthenticationFinder
{
    public function __construct(EntityManagerInterface $entityManager, MessageBus $eventBus, string $className = Administrator::class)
    {
        parent::__construct($entityManager, $eventBus, $className);
    }

    public function get(AdministratorId $id): Administrator
    {
        $administrator = $this->find($id);

        if ($administrator === null) {
            throw AdministratorNotFound::withId($id);
        }

        return $administrator;
    }

    public function save(Administrator $administrator): void
    {
        $this->_em->persist($administrator);

        $this->doDispatchEvents($administrator);
    }

    public function remove(Administrator $administrator): void
    {
        $this->_em->remove($administrator);
    }

    public function getByEmail(EmailAddress $email): Administrator
    {
        $administrator = $this->createQueryBuilder('u')
            ->where('u.email.canonical = :email')
            ->getQuery()
            ->setParameter('email', $email->canonical)
            ->getOneOrNullResult();

        if ($administrator === null) {
            throw AdministratorNotFound::withEmail($email);
        }

        return $administrator;
    }

    public function getByPasswordResetToken(string $selector): Administrator
    {
        $administrator = $this->createQueryBuilder('u')
            ->where('u.passwordResetToken.selector = :selector')
            ->getQuery()
            ->setParameter('selector', $selector)
            ->getOneOrNullResult();

        if ($administrator === null) {
            throw new PasswordResetTokenNotAccepted();
        }

        return $administrator;
    }

    public function findAuthenticationByEmail(string $email): ?SecurityUser
    {
        /** @var Administrator $administrator */
        $administrator = $this->createQueryBuilder('u')
            ->where('u.email.canonical = :email')
            ->getQuery()
            ->setParameter('email', $email)
            ->getOneOrNullResult();

        if ($administrator !== null) {
            return $administrator->toSecurityUser();
        }

        return null;
    }

    public function findAuthenticationById(string $id): ?SecurityUser
    {
        /** @var Administrator $administrator */
        $administrator = $this->createQueryBuilder('u')
            ->where('u.id = :id')
            ->getQuery()
            ->setParameter('id', $id)
            ->getOneOrNullResult();

        if ($administrator !== null) {
            return $administrator->toSecurityUser();
        }

        return null;
    }
}
