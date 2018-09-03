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

namespace ParkManager\Module\WebhostingModule\Domain\Package\Capability;

use ParkManager\Module\WebhostingModule\Domain\Package\Capability;

final class MonthlyTrafficQuota implements Capability
{
    private $quota;

    public function __construct(string $quota)
    {
        $this->quota = $quota;
    }

    public static function id(): string
    {
        return 'f9eeab0e-a38f-11e7-939d-acbc32b58315';
    }

    public function configuration(): array
    {
        return ['quota' => $this->quota];
    }

    public static function reconstituteFromArray(array $from): self
    {
        return new self($from['quota']);
    }
}
