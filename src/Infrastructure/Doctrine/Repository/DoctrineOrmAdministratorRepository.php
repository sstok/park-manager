<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Domain\Administrator\Administrator;
use ParkManager\Domain\Administrator\AdministratorId;
use ParkManager\Domain\Administrator\AdministratorRepository;
use ParkManager\Domain\Administrator\Exception\AdministratorNotFound;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Exception\PasswordResetTokenNotAccepted;
use ParkManager\Infrastructure\Security\AuthenticationFinder;
use ParkManager\Infrastructure\Security\SecurityUser;

/**
 * @method Administrator find($id, $lockMode = null, $lockVersion = null)
 */
class DoctrineOrmAdministratorRepository extends EntityRepository implements AdministratorRepository, AuthenticationFinder
{
    public function __construct(EntityManagerInterface $entityManager, string $className = Administrator::class)
    {
        parent::__construct($entityManager, $className);
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
