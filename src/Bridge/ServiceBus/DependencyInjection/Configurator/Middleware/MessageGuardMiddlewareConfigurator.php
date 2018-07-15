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

namespace ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\Middleware;

use ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\MessageBusConfigurator;
use ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\MiddlewareConfigurator;
use ParkManager\Component\ServiceBus\MessageGuard\MessageGuardMiddleware;
use ParkManager\Component\ServiceBus\MessageGuard\PermissionGuard;
use Symfony\Component\DependencyInjection\Loader\Configurator\AbstractServiceConfigurator;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class MessageGuardMiddlewareConfigurator implements MiddlewareConfigurator
{
    private $di;
    private $serviceId;

    /**
     * @param AbstractServiceConfigurator $di
     * @param string                      $serviceId
     * @param string|array                ...$guards
     */
    public function __construct(AbstractServiceConfigurator $di, string $serviceId, ...$guards)
    {
        $this->di = $di;
        $this->serviceId = $serviceId;

        $di->set($serviceId.'.middleware.message_guard', MessageGuardMiddleware::class)
            ->tag($serviceId.'.middleware', ['priority' => MessageBusConfigurator::MIDDLEWARE_PRIORITY_GUARD])->autowire(false)->private();

        foreach ($guards as $guard) {
            if (\is_array($guard)) {
                $this->registerGuard($guard[0], $guard[1] ?? 0, $guard[2] ?? []);
            } elseif (\is_string($guard)) {
                $this->registerGuard($guard);
            } else {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Invalid guard provided for MessageGuardMiddlewareConfigurator with MessageBus (%s), expected string or array. Got "%s".',
                        $serviceId,
                        \gettype($guard)
                    )
                );
            }
        }
    }

    private function registerGuard(string $className, int $priority = 0, array $args = []): void
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException(sprintf('Invalid guard provided, class %s does not exist.', $className));
        }

        if (!is_a($className, PermissionGuard::class, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid guard provided, class %s does not implement %s.', $className, PermissionGuard::class)
            );
        }

        $this->di->set($this->serviceId.'.message_guard.'.$className, $className)
            ->tag($this->serviceId.'.message_guard', ['priority' => $priority])
            ->args($args)
            ->private();
    }
}
