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

namespace ParkManager\Module\Webhosting\Service\Package;

use ParkManager\Module\Webhosting\Model\Package\CapabilitiesFactory;
use ParkManager\Module\Webhosting\Model\Package\Capability;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class CapabilitiesManager implements CapabilitiesFactory
{
    private $capabilities;

    public function __construct()
    {
        $this->capabilities = [
            Capability\MonthlyTrafficQuota::id() => Capability\MonthlyTrafficQuota::class,
        ];
    }

    public function createById(string $id, array $options): Capability
    {
        return $this->capabilities[$id]::reconstituteFromArray($options);
    }

    public function createByName(string $className, array $options): Capability
    {
        return $className::reconstituteFromArray($options);
    }
}
