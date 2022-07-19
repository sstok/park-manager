<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Security;

use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\CredentialsExpiredException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class SecurityUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (! $user instanceof SecurityUser) {
            return;
        }

        if (! $user->isEnabled()) {
            $ex = new DisabledException('User account is disabled.');
            $ex->setUser($user);

            throw $ex;
        }

        if ($user->isAccountExpired()) {
            $ex = new AccountExpiredException('User account has expired.');
            $ex->setUser($user);

            throw $ex;
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (! $user instanceof SecurityUser) {
            return;
        }

        if ($user->isCredentialsExpired()) {
            $ex = new CredentialsExpiredException('User credentials have expired.');
            $ex->setUser($user);

            throw $ex;
        }
    }
}
