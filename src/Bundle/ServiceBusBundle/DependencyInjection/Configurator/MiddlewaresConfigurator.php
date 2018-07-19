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

namespace ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator;

use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Middleware;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\Middleware\DomainEventsMiddlewareConfigurator;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\Middleware\PolicyGuardMiddlewareConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\AbstractServiceConfigurator;

/**
 * @internal
 *
 * @method self                               doctrineOrmTransaction(string $managerName)
 * @method self                               doctrineDbalTransaction(string $managerName)
 * @method self                               messageGuard(...$guards)
 * @method DomainEventsMiddlewareConfigurator domainEvents()
 * @method PolicyGuardMiddlewareConfigurator  policyGuard(?string $namespacePrefix = '', ?int $priority = -10)
 *
 * @final
 */
class MiddlewaresConfigurator
{
    private $parent;
    private $serviceId;
    private $di;

    public function __construct(MessageBusConfigurator $parent, AbstractServiceConfigurator $di, string $serviceId)
    {
        $this->parent = $parent;
        $this->serviceId = $serviceId;
        $this->di = $di;
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return AdvancedMiddlewareConfigurator|$this Returns the MiddlewareConfigurator for advanced configurators
     */
    public function __call(string $name, array $arguments): object
    {
        $className = __NAMESPACE__.'\\Middleware\\'.ucfirst($name).'MiddlewareConfigurator';

        if (!class_exists($className)) {
            throw new \InvalidArgumentException(sprintf('Cannot locate class "%s" for middleware %s.', $className, $name));
        }

        if (!is_a($className, MiddlewareConfigurator::class, true)) {
            throw new \InvalidArgumentException(sprintf('Class %s must implement %s.', $className, MiddlewareConfigurator::class));
        }

        if (is_a($className, AdvancedMiddlewareConfigurator::class, true)) {
            return new $className(...array_merge([$this, $this->di, $this->serviceId], $arguments));
        }

        new $className(...array_merge([$this->di, $this->serviceId], $arguments));

        return $this;
    }

    public function register(string $middlewareClass, int $priority = 0, $arguments = []): self
    {
        if (CommandHandlerMiddleware::class === $middlewareClass) {
            throw new \InvalidArgumentException(sprintf('Cannot register %s as this is already done by the configurator.', CommandHandlerMiddleware::class));
        }

        if (!class_exists($middlewareClass)) {
            throw new \InvalidArgumentException(sprintf('Cannot locate class %s.', $middlewareClass));
        }

        if (!is_a($middlewareClass, Middleware::class, true)) {
            throw new \InvalidArgumentException(sprintf('Class %s must implement %s.', $middlewareClass, Middleware::class));
        }

        $this->di->set($this->serviceId.'.middleware.'.$middlewareClass, $middlewareClass)
            ->tag($this->serviceId.'.middleware', ['priority' => $priority])
            ->args($arguments)
            ->private();

        return $this;
    }

    public function end(): MessageBusConfigurator
    {
        return $this->parent;
    }
}
