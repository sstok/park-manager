<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service;

use ParkManager\Application\Service\SystemGateway\OperationResult;
use ParkManager\Application\Service\SystemGateway\SystemCommand;
use ParkManager\Application\Service\SystemGateway\SystemQuery;

/**
 * The SystemGateway communicates with the application-system
 * to execute commands and query for information.
 *
 * Each Command or Query operation must return an associated OperationResult.
 * For RegisterUserCommand this must return a RegisterUserResult object.
 */
interface SystemGateway
{
    /**
     * Sends a SystemCommand to application-system for execution,
     * and returns the operations result.
     */
    public function execute(SystemCommand $command): OperationResult;

    /**
     * Sends a SystemQuery to request information on the application-system.
     *
     * This information might be cached.
     */
    public function query(SystemQuery $command): OperationResult;
}
