<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Domain\Package\Event;

use ParkManager\Component\DomainEvent\DomainEvent;
use ParkManager\Bundle\WebhostingBundle\Domain\Package\Capabilities;
use ParkManager\Bundle\WebhostingBundle\Domain\Package\WebhostingPackageId;

final class WebhostingPackageCapabilitiesWasChanged extends DomainEvent
{
    private $id;
    private $capabilities;

    public function __construct(WebhostingPackageId $id, Capabilities $capabilities)
    {
        $this->id           = $id;
        $this->capabilities = $capabilities;
    }

    public function id(): WebhostingPackageId
    {
        return $this->id;
    }

    public function capabilities(): Capabilities
    {
        return $this->capabilities;
    }
}
