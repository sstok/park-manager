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

namespace ParkManager\Bundle\ServiceBusBundle\Guard;

use ParkManager\Component\ServiceBus\MessageGuard\PermissionGuard;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The SymfonyGuard the Symfony Security Component to allow for access.
 *
 * If the Symfony Security system did not grant access the decision
 * is abstained.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class SymfonyGuard implements PermissionGuard
{
    private $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function decide(object $message): int
    {
        if ($this->authorizationChecker->isGranted([], $message)) {
            return self::PERMISSION_ALLOW;
        }

        return self::PERMISSION_ABSTAIN;
    }
}
