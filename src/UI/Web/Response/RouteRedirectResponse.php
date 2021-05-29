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
    /** @var array<string, mixed> */
    private array $parameters;
    private int $status;
    /** @var array<int, array{0: string, 1: string, 2: array<string, mixed>|null}> */
    private array $flashes = [];

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(string $route, array $parameters = [], int $status = 302)
    {
        $this->route = $route;
        $this->parameters = $parameters;
        $this->status = $status;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public static function toRoute(string $route, array $parameters = []): self
    {
        return new self($route, $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public static function permanent(string $route, array $parameters = []): self
    {
        return new self($route, $parameters, Response::HTTP_MOVED_PERMANENTLY);
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param array<string, mixed>|null $arguments
     *
     * @return $this
     */
    public function withFlash(string $type, string $message, ?array $arguments = null): self
    {
        $this->flashes[] = [$type, $message, $arguments];

        return $this;
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: array<string, mixed>|null}>
     */
    public function getFlashes(): array
    {
        return $this->flashes;
    }
}
