<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Security;

use InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class UserProvider implements UserProviderInterface
{
    /** @var AuthenticationFinder */
    private $repository;

    /** @var string */
    private $userClass;

    public function __construct(AuthenticationFinder $repository, string $userClass)
    {
        $this->repository = $repository;
        $this->userClass = $userClass;

        if (! \is_subclass_of($userClass, SecurityUser::class, true)) {
            throw new InvalidArgumentException(
                \sprintf('Expected UserClass (%s) to be a child of "%s"', $userClass, SecurityUser::class)
            );
        }
    }

    public function loadUserByUsername($username): SecurityUser
    {
        $user = $this->repository->findAuthenticationByEmail($username);

        if ($user === null) {
            $e = new UsernameNotFoundException();
            $e->setUsername($username);

            throw $e;
        }

        return $user;
    }

    /**
     * @param SecurityUser $user
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (! $user instanceof $this->userClass) {
            throw new UnsupportedUserException(\sprintf('Expected an instance of %s, but got "%s".', $this->userClass, \get_class($user)));
        }

        $storedUser = $this->repository->findAuthenticationById($user->getUsername());

        if ($storedUser === null) {
            $e = new UsernameNotFoundException();
            $e->setUsername($user->getUsername());

            throw $e;
        }

        return $storedUser;
    }

    public function supportsClass($class): bool
    {
        return $this->userClass === $class;
    }
}
