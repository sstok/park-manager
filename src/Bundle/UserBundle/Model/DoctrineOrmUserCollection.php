<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\UserBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use ParkManager\Component\Model\Event\EventEmitter;
use ParkManager\Component\User\Exception\UserNotFound;
use ParkManager\Component\User\Model\User;
use ParkManager\Component\User\Model\UserCollection;
use ParkManager\Component\User\Model\UserId;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 *
 * @method User findOneBy(array $criteria, array $orderBy = null)
 */
class DoctrineOrmUserCollection extends EntityRepository implements UserCollection
{
    protected $eventEmitter;

    public function __construct(EntityManagerInterface $entityManager, EventEmitter $eventEmitter, string $className)
    {
        $this->_em = $entityManager;
        $this->_class = $entityManager->getClassMetadata($className);
        $this->_entityName = $className;
        $this->eventEmitter = $eventEmitter;
    }

    public function get(UserId $id): User
    {
        /** @var User $user */
        if (null === $user = $this->find($id)) {
            throw UserNotFound::withUserId($id);
        }

        return $user;
    }

    public function save(User $user): void
    {
        $this->_em->transactional(function () use ($user) {
            $this->_em->persist($user);
        });

        foreach ($user->releaseEvents() as $event) {
            $this->eventEmitter->emit($event);
        }
    }

    public function remove(User $user): void
    {
        $this->_em->transactional(function () use ($user) {
            $this->_em->remove($user);
        });
    }

    public function findByEmailAddress(string $email): ?User
    {
        return $this->findOneBy(['canonicalEmail' => $email]);
    }

    public function findByEmailAddressChangeToken(string $selector): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.emailAddressChangeToken.selector = :selector')
            ->getQuery()
            ->setParameter('selector', $selector)
            ->getOneOrNullResult()
        ;
    }

    public function findByPasswordResetToken(string $selector): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.passwordResetToken.selector = :selector')
            ->getQuery()
            ->setParameter('selector', $selector)
            ->getOneOrNullResult()
        ;
    }
}
