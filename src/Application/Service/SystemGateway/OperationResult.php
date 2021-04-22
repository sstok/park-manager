<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\SystemGateway;

abstract class OperationResult
{
    protected array $parameters;

    final public function __construct(array $parameters)
    {
        $this->validateParameters($parameters);

        $this->parameters = $parameters;
    }

    protected function validateParameters(array $parameters): void
    {
        // No-op. Template method.
    }
}
