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

namespace ParkManager\Bundle\RouteAutofillBundle\Response;

use Symfony\Component\HttpFoundation\Response;

class RouteRedirectResponse
{
    protected $route;
    protected $parameters = [];
    protected $status = 302;

    public function __construct(string $route, array $parameters = [], int $status = 302)
    {
        $this->route = $route;
        $this->parameters = $parameters;
        $this->status = $status;
    }

    public static function permanent(string $route, array $parameters = [])
    {
        return new static($route, $parameters, Response::HTTP_MOVED_PERMANENTLY);
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
