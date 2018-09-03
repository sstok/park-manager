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

namespace ParkManager\Module\WebhostingModule\Tests\Fixtures\Domain\PackageCapability;

use ParkManager\Module\WebhostingModule\Domain\Package\Capability;

final class MonthlyTrafficQuota implements Capability
{
    private $limit;

    public static function id(): string
    {
        return '04da51e0-97c8-11e7-b2eb-acbc32b58315';
    }

    public function __construct(int $limit)
    {
        $this->limit = $limit;
    }

    public function configuration(): array
    {
        return ['limit' => $this->limit];
    }

    public static function reconstituteFromArray(array $from): self
    {
        return new self($from['limit']);
    }
}
