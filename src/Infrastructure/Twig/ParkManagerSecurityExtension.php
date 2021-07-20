<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Twig;

use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\User\UserRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ParkManagerSecurityExtension extends AbstractExtension
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private UserRepository $userRepository
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_current_user', [$this, 'getCurrentUser']),
        ];
    }

    public function getCurrentUser(): User
    {
        $token = $this->tokenStorage->getToken();

        if ($token === null) {
            throw new AccessDeniedException();
        }

        return $this->userRepository->get(UserId::fromString($token->getUserIdentifier()));
    }
}
