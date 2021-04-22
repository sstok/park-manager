<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\SystemGateway\Webhosting;

use Assert\Assertion;
use ParkManager\Application\Service\SystemGateway\OperationResult;

final class RegisterSystemUserResult extends OperationResult
{
    protected function validateParameters(array $parameters): void
    {
        Assertion::keyExists($parameters, 'id');
        Assertion::keyExists($parameters, 'groups');
        Assertion::keyExists($parameters, 'homedir');
    }

    public function userId(): int
    {
        return $this->parameters['id'];
    }

    /**
     * @return array<int>
     */
    public function userGroups(): array
    {
        return $this->parameters['groups'];
    }

    public function homeDirectory(): string
    {
        return $this->parameters['homedir'];
    }
}
