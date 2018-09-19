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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use const PHP_SAPI;

/**
 * Allows access when SAPI is cli and no Security-token was set.
 */
final class CliGuard implements PermissionGuard
{
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function decide(object $message): int
    {
        if (PHP_SAPI === 'cli' && $this->tokenStorage->getToken() === null) {
            return self::PERMISSION_ALLOW;
        }

        return self::PERMISSION_ABSTAIN;
    }
}
