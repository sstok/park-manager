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

namespace ParkManager\Component\ServiceBus\MessageGuard;

use League\Tactician\Middleware;
use Psr\Log\LoggerInterface;

final class MessageGuardMiddleware implements Middleware
{
    private $guards;
    private $logger;

    /**
     * @param iterable|PermissionGuard[] $guards When a generator be sure to allow rewinding
     * @param LoggerInterface|null       $logger
     */
    public function __construct(iterable $guards, LoggerInterface $logger = null)
    {
        $this->guards = $guards;
        $this->logger = $logger;
    }

    public function execute($command, callable $next)
    {
        $decision = PermissionGuard::PERMISSION_DENY;

        foreach ($this->guards as $guard) {
            $decision = $guard->decide($command);

            if ($decision < -1 || $decision > 1) {
                throw new \InvalidArgumentException(sprintf('PermissionGuard "%s" returned unsupported decision %d', \get_class($guard), $decision));
            }

            if ($this->logger) {
                $this->logger->info(
                    sprintf('PermissionGuard "%s" decides: %s', \get_class($guard), [0 => 'DENY', 1 => 'ALLOW', -1 => 'ABSTAIN'][$decision])
                );
            }

            if ($decision !== PermissionGuard::PERMISSION_ABSTAIN) {
                break;
            }
        }

        if (PermissionGuard::PERMISSION_DENY === $decision || PermissionGuard::PERMISSION_ABSTAIN === $decision) {
            throw UnauthorizedException::forMessage($command);
        }

        return $next($command);
    }
}
