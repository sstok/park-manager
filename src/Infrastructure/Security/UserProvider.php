<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Security;

use ParkManager\Domain\Administrator\AdministratorId;
use ParkManager\Domain\Administrator\AdministratorRepository;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Exception\NotFoundException;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\User\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class UserProvider implements UserProviderInterface
{
    /** @var UserRepository */
    private $userRepository;

    /** @var AdministratorRepository */
    private $administratorRepository;

    public function __construct(UserRepository $userRepository, AdministratorRepository $administratorRepository)
    {
        $this->userRepository = $userRepository;
        $this->administratorRepository = $administratorRepository;
    }

    public function loadUserByUsername(string $username): SecurityUser
    {
        [$type, $email] = \explode("\0", $username, 2);

        try {
            if ($type === 'admin') {
                $user = $this->administratorRepository->getByEmail(new EmailAddress($email));
            } else {
                $user = $this->userRepository->getByEmail(new EmailAddress($email));
            }
        } catch (NotFoundException $e) {
            $e = new UsernameNotFoundException('', 0, $e);
            $e->setUsername($email);

            throw $e;
        }

        return $user->toSecurityUser();
    }

    /**
     * @param SecurityUser $user
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (! $user instanceof SecurityUser) {
            throw new UnsupportedUserException(\sprintf('Expected an instance of %s, but got "%s".', SecurityUser::class, \get_class($user)));
        }

        try {
            if ($user->isAdmin()) {
                $storedUser = $this->administratorRepository->get(AdministratorId::fromString($user->getUsername()));
            } else {
                $storedUser = $this->userRepository->get(UserId::fromString($user->getUsername()));
            }
        } catch (NotFoundException $e) {
            $e = new UsernameNotFoundException('', 0, $e);
            $e->setUsername($user->getUsername());

            throw $e;
        }

        return $storedUser->toSecurityUser();
    }

    public function supportsClass(string $class): bool
    {
        return $class === SecurityUser::class;
    }
}
