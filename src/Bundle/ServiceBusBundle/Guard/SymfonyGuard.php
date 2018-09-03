<?php

declare(strict_types=1);

/*
 * This file is part of the Park-Manager project.
 *
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Bundle\ServiceBusBundle\Guard;

use ParkManager\Component\ServiceBus\MessageGuard\PermissionGuard;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The SymfonyGuard the Symfony Security Component to allow for access.
 *
 * If the Symfony Security system did not grant access the decision
 * is abstained.
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
