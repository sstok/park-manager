<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain;

use Carbon\CarbonImmutable;
use Doctrine\ORM\Mapping\Column;

trait TimestampableTrait
{
    #[Column(name: 'registered_at', type: 'carbon_immutable', nullable: true)]
    private CarbonImmutable $registeredAt;

    #[Column(name: 'updated_at', type: 'carbon_immutable', nullable: true)]
    private CarbonImmutable $updatedAt;

    public function getRegisteredAt(): CarbonImmutable
    {
        return $this->registeredAt ?? $this->getUpdatedAt();
    }

    public function getUpdatedAt(): CarbonImmutable
    {
        if (! isset($this->updatedAt)) {
            $this->__updateTimestamp();
        }

        return $this->updatedAt;
    }

    protected function __updateTimestamp(): void
    {
        // Create a datetime with microseconds
        $this->updatedAt = CarbonImmutable::createFromFormat('U.u', \sprintf('%.6F', microtime(true)));

        if (! isset($this->registeredAt)) {
            $this->registeredAt = $this->updatedAt;
        }
    }
}
