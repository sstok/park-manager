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

namespace ParkManager\Bundle\UserBundle\Security;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use ParkManager\Component\User\Canonicalizer\Canonicalizer;
use ParkManager\Component\User\Security\SecurityUser;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class DbalUserProvider implements UserProviderInterface
{
    private $connection;
    private $userTable;
    private $userClass;

    /**
     * @var Canonicalizer
     */
    private $emailCanonicalizer;

    public function __construct(Connection $connection, Canonicalizer $emailCanonicalizer, string $userTable, string $userClass)
    {
        $this->connection = $connection;
        $this->emailCanonicalizer = $emailCanonicalizer;
        $this->userTable = $userTable;
        $this->userClass = $userClass;

        if (!is_subclass_of($userClass, SecurityUser::class, true)) {
            throw new \InvalidArgumentException(
                sprintf('Expected UserClass (%s) to be a child of "%s"', $userClass, SecurityUser::class)
            );
        }
    }

    public function loadUserByUsername($username): UserInterface
    {
        $username = $this->emailCanonicalizer->canonicalize($username);
        $user = $this->connection->fetchAssoc(
            "SELECT id, auth_password, access_enabled, roles FROM {$this->userTable} WHERE canonical_email = :email",
            ['email' => $username]
        );

        if (false === $user) {
            $e = new UsernameNotFoundException();
            $e->setUsername($username);

            throw $e;
        }

        return $this->createUser($user);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof $this->userClass) {
            throw new UnsupportedUserException(sprintf('Expected an instance of %s, but got "%s".', $this->userClass, \get_class($user)));
        }

        $fetchedUser = $this->connection->fetchAssoc(
            "SELECT id, auth_password, access_enabled, roles FROM {$this->userTable} WHERE id = :id",
            ['id' => $user->getUsername()]
        );

        if (false === $fetchedUser) {
            $e = new UsernameNotFoundException();
            $e->setUsername($user->getUsername());

            throw $e;
        }

        return $this->createUser($fetchedUser);
    }

    public function supportsClass($class): bool
    {
        return $this->userClass === $class;
    }

    private function createUser(array $user): SecurityUser
    {
        return new $this->userClass(
            $user['id'],
            (string) $user['auth_password'],
            Type::getType(Type::BOOLEAN)->convertToPHPValue($user['access_enabled'], $this->connection->getDatabasePlatform()),
            null === $user['roles'] ? [] : json_decode($user['roles'], true)
        );
    }
}
