<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Security;

use ParkManager\Application\Command\User\ChangeUserPassword;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Exception\MalformedEmailAddress;
use ParkManager\Domain\Exception\NotFoundException;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\User\UserRepository;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Throwable;

final class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(private UserRepository $repository, private MessageBusInterface $commandBus)
    {
    }

    public function loadUserByIdentifier(string $identifier): SecurityUser
    {
        try {
            $user = $this->repository->getByEmail(new EmailAddress($identifier));
        } catch (NotFoundException | MalformedEmailAddress $e) {
            $e = new UserNotFoundException('', 0, $e);
            $e->setUserIdentifier($identifier);

            throw $e;
        }

        return $user->toSecurityUser();
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (! $user instanceof SecurityUser) {
            throw new UnsupportedUserException(sprintf('Expected an instance of %s, but got "%s".', SecurityUser::class, \get_class($user)));
        }

        try {
            $storedUser = $this->repository->get(UserId::fromString($user->getUserIdentifier()));
        } catch (NotFoundException $e) {
            $e = new UserNotFoundException('', 0, $e);
            $e->setUserIdentifier($user->getUserIdentifier());

            throw $e;
        }

        return $storedUser->toSecurityUser();
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface | UserInterface $user, string $newHashedPassword): void
    {
        if (! $user instanceof SecurityUser) {
            throw new UnsupportedUserException(sprintf('Expected an instance of %s, but got "%s".', SecurityUser::class, \get_class($user)));
        }

        try {
            $entity = $this->repository->get($id = UserId::fromString($user->getId()));

            if ($entity->hasPasswordResetPending() || $entity->isPasswordExpired()) {
                return;
            }

            $this->commandBus->dispatch(new ChangeUserPassword($id, $newHashedPassword));
        } catch (Throwable) {
            // Noop
        }
    }

    public function supportsClass(string $class): bool
    {
        return $class === SecurityUser::class;
    }

    public function loadUserByUsername(string $username)
    {
        return $this->loadUserByIdentifier($username);
    }
}
