<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\CoreModule\Application\Command;

/**
 * Allows to combine multiple Commands into a single transaction.
 */
final class BatchCommand
{
    /**
     * READ-ONLY.
     *
     * @var object[]
     */
    public $commands;

    public function __construct(object ...$commands)
    {
        $this->commands = $commands;
    }
}
