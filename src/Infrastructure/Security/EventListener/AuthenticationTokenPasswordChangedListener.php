<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Security\EventListener;

use ParkManager\Application\Event\UserPasswordWasChanged;
use ParkManager\Infrastructure\Security\SecurityUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

/**
 * Updates the current AuthenticationToken when the *current* user changes
 * their login password.
 */
final class AuthenticationTokenPasswordChangedListener
{
    public function __construct(
        private UserProviderInterface $userProvider,
        private TokenStorageInterface $tokenStorage
    ) {
    }

    public function onUserPasswordWasChanged(UserPasswordWasChanged $event): void
    {
        $token = $this->tokenStorage->getToken();

        if ($token === null || ! $token->isAuthenticated()) {
            return;
        }

        // Don't update when the token is actually an impersonation.
        if ($token instanceof SwitchUserToken) {
            return;
        }

        $user = $token->getUser();

        if (! $user instanceof SecurityUser) {
            return;
        }

        if ($event->id !== $token->getUserIdentifier()) {
            return;
        }

        $user = $this->userProvider->refreshUser($user);
        \assert($user instanceof SecurityUser);

        if (! $user->isEnabled()) {
            return;
        }

        \assert(method_exists($token, 'getFirewallName'));

        $token = new PostAuthenticationToken($user, $token->getFirewallName(), $token->getRoleNames());
        $this->tokenStorage->setToken($token);
    }
}
