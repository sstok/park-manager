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

namespace ParkManager\Bundle\ServiceBusBundle\DependencyInjection;

use League\Tactician\Exception\MissingHandlerException;
use League\Tactician\Handler\Locator\HandlerLocator;
use Psr\Container\ContainerInterface;

/**
 * Fetches handled instance from a reduced Symfony ServiceLocator container.
 *
 * Handlers are located by message-name.
 */
final class ContainerLocator implements HandlerLocator
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getHandlerForCommand($commandName): object
    {
        if (! $this->container->has($commandName)) {
            throw MissingHandlerException::forCommand($commandName);
        }

        return $this->container->get($commandName);
    }
}
