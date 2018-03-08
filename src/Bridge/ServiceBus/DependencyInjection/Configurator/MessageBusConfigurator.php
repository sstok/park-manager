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

namespace ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator;

use League\Tactician\CommandBus;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 *
 * @final
 */
class MessageBusConfigurator
{
    // Priorities the higher the sooner.
    // --
    public const MIDDLEWARE_PRIORITY_GUARD = 10000;
    public const MIDDLEWARE_PRIORITY_TRANSACTION = 8000;
    public const MIDDLEWARE_PRIORITY_HANDLE = -10000;
    // --

    private $di;
    private $serviceId;

    public static function register(DefaultsConfigurator $di, string $serviceId): self
    {
        $serviceBus = $di->set($serviceId, CommandBus::class)->private();
        $serviceBus->tag('park_manager.service_bus');

        return new static($di, $serviceId);
    }

    public static function extend(DefaultsConfigurator $di, string $serviceId): self
    {
        return new static($di, $serviceId);
    }

    public function middlewares(): MiddlewaresConfigurator
    {
        return new MiddlewaresConfigurator($this, $this->di, $this->serviceId);
    }

    /**
     * @param string|null $searchDirectory Absolute directory to locate load()s from
     *                                     (falls back to "current" services.php file directory)
     *
     * @return HandlersConfigurator
     */
    public function handlers(string $searchDirectory = null): HandlersConfigurator
    {
        if (null === $searchDirectory) {
            $searchDirectory = \dirname(debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS)[0]['file']);
        }

        return new HandlersConfigurator($this, $this->di, $this->serviceId, $searchDirectory);
    }

    protected function __construct(DefaultsConfigurator $di, string $serviceId)
    {
        $this->di = $di;
        $this->serviceId = $serviceId;
    }
}
