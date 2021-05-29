<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\SystemGateway;

abstract class SystemQuery
{
    /** @var array<string, mixed> */
    private array $arguments;

    /**
     * @param array<string, mixed> $arguments
     */
    protected function __construct(array $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @return array<string, mixed>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
