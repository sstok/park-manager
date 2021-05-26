<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Exception\PasswordResetTokenNotAccepted;
use ParkManager\Domain\ResultSet;
use ParkManager\Domain\User\Exception\EmailChangeConfirmationRejected;
use ParkManager\Domain\User\Exception\UserNotFound;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\User\UserRepository;
use ParkManager\Infrastructure\Doctrine\OrmQueryBuilderResultSet;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 */
class DoctrineOrmUserRepository extends EntityRepository implements UserRepository
{
    public function __construct(EntityManagerInterface $entityManager, string $className = User::class)
    {
        parent::__construct($entityManager, $className);
    }

    public function get(UserId $id): User
    {
        $user = $this->find($id);

        if ($user === null) {
            throw UserNotFound::withId($id);
        }

        return $user;
    }

    public function save(User $user): void
    {
        $this->updateTimestamp($user);
        $this->_em->persist($user);
    }

    public function remove(User $user): void
    {
        $this->_em->remove($user);
    }

    public function getByEmail(EmailAddress $email): User
    {
        $user = $this->createQueryBuilder('u')
            ->where('u.email.canonical = :email')
            ->getQuery()
            ->setParameter('email', $email->canonical)
            ->getOneOrNullResult()
        ;

        if ($user === null) {
            throw UserNotFound::withEmail($email);
        }

        return $user;
    }

    public function all(): ResultSet
    {
        return (new OrmQueryBuilderResultSet($this->createQueryBuilder('u'), 'u', false))->setOrdering('u.registeredAt', 'DESC');
    }

    public function getByEmailAddressChangeToken(string $selector): User
    {
        $user = $this->createQueryBuilder('u')
            ->where('u.emailAddressChangeToken.selector = :selector')
            ->getQuery()
            ->setParameter('selector', $selector)
            ->getOneOrNullResult()
        ;

        if ($user === null) {
            throw new EmailChangeConfirmationRejected();
        }

        return $user;
    }

    public function getByPasswordResetToken(string $selector): User
    {
        $user = $this->createQueryBuilder('u')
            ->where('u.passwordResetToken.selector = :selector')
            ->getQuery()
            ->setParameter('selector', $selector)
            ->getOneOrNullResult()
        ;

        if ($user === null) {
            throw new PasswordResetTokenNotAccepted();
        }

        return $user;
    }
}
