<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Infrastructure\Service\Package;

use ParkManager\Module\WebhostingModule\Domain\Package\Capabilities;
use ParkManager\Module\WebhostingModule\Domain\Package\Exception\CapabilityNotRegistered;

final class CapabilitiesFactory
{
    private $capabilitiesById;

    public function __construct(array $capabilitiesById)
    {
        $this->capabilitiesById = $capabilitiesById;
    }

    /**
     * Reconstitutes a Capabilities set from storage.
     */
    public function reconstituteFromStorage(array $capabilities): Capabilities
    {
        $capabilitiesInstances = [];

        foreach ($capabilities as $id => $configuration) {
            if (! isset($this->capabilitiesById[$id])) {
                throw CapabilityNotRegistered::withId($id);
            }

            $capabilitiesInstances[] = $this->capabilitiesById[$id]::reconstituteFromArray($configuration);
        }

        return new Capabilities(...$capabilitiesInstances);
    }
}
