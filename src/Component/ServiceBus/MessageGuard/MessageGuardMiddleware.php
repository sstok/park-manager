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
