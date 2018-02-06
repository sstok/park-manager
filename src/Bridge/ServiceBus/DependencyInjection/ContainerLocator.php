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

namespace ParkManager\Bridge\ServiceBus\DependencyInjection;

use League\Tactician\Exception\MissingHandlerException;
use League\Tactician\Handler\Locator\HandlerLocator;
use Psr\Container\ContainerInterface;

/**
 * Fetches handled instance from a reduced Symfony ServiceLocator container.
 *
 * Handlers are located by message-name.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
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
        if (!$this->container->has($commandName)) {
            throw MissingHandlerException::forCommand($commandName);
        }

        return $this->container->get($commandName);
    }
}
