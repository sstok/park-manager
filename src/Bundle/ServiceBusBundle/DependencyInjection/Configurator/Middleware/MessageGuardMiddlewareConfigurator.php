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

namespace ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\Middleware;

use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\MessageBusConfigurator;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\MiddlewareConfigurator;
use ParkManager\Component\ServiceBus\MessageGuard\MessageGuardMiddleware;
use ParkManager\Component\ServiceBus\MessageGuard\PermissionGuard;
use Symfony\Component\DependencyInjection\Loader\Configurator\AbstractServiceConfigurator;
use function class_exists;
use function gettype;
use function is_a;
use function is_array;
use function is_string;
use function sprintf;

final class MessageGuardMiddlewareConfigurator implements MiddlewareConfigurator
{
    private $di;
    private $serviceId;

    /**
     * @param string|array ...$guards Per variadic: Either a Guard class-name or an array
     *                                with [class-name, priority, arguments]
     */
    public function __construct(AbstractServiceConfigurator $di, string $serviceId, ...$guards)
    {
        $this->di        = $di;
        $this->serviceId = $serviceId;

        $di->set($serviceId . '.middleware.message_guard', MessageGuardMiddleware::class)
            ->tag($serviceId . '.middleware', ['priority' => MessageBusConfigurator::MIDDLEWARE_PRIORITY_GUARD])
            ->autowire(false)
            ->private();

        foreach ($guards as $guard) {
            if (is_array($guard)) {
                $this->registerGuard($guard[0], $guard[1] ?? 0, $guard[2] ?? []);
            } elseif (is_string($guard)) {
                $this->registerGuard($guard);
            } else {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Invalid guard provided for MessageGuardMiddlewareConfigurator with MessageBus (%s), expected string or array. Got "%s".',
                        $serviceId,
                        gettype($guard)
                    )
                );
            }
        }
    }

    private function registerGuard(string $className, int $priority = 0, array $args = []): void
    {
        if (! class_exists($className)) {
            throw new \InvalidArgumentException(sprintf('Invalid guard provided, class %s does not exist.', $className));
        }

        if (! is_a($className, PermissionGuard::class, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid guard provided, class %s does not implement %s.', $className, PermissionGuard::class)
            );
        }

        $this->di->set($this->serviceId . '.message_guard.' . $className, $className)
            ->tag($this->serviceId . '.message_guard', ['priority' => $priority])
            ->args($args)
            ->private();
    }
}
