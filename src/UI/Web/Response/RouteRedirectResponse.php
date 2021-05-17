<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Response;

use Symfony\Component\HttpFoundation\Response;

final class RouteRedirectResponse
{
    private string $route;
    private array $parameters;
    private int $status;
    private array $flashes = [];

    public function __construct(string $route, array $parameters = [], int $status = 302)
    {
        $this->route = $route;
        $this->parameters = $parameters;
        $this->status = $status;
    }

    public static function toRoute(string $route, array $parameters = []): self
    {
        return new self($route, $parameters);
    }

    public static function permanent(string $route, array $parameters = []): self
    {
        return new self($route, $parameters, Response::HTTP_MOVED_PERMANENTLY);
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

    public function withFlash(string $type, string $message, ?array $arguments = null): self
    {
        $this->flashes[] = [$type, $message, $arguments];

        return $this;
    }

    public function getFlashes(): array
    {
        return $this->flashes;
    }
}
