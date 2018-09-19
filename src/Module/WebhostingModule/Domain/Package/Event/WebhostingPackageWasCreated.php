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

namespace ParkManager\Module\WebhostingModule\Domain\Package\Event;

use ParkManager\Component\DomainEvent\DomainEvent;
use ParkManager\Module\WebhostingModule\Domain\Package\Capabilities;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackageId;

final class WebhostingPackageWasCreated extends DomainEvent
{
    private $packageId;
    private $capabilities;

    public function __construct(WebhostingPackageId $id, Capabilities $capabilities)
    {
        $this->packageId    = $id;
        $this->capabilities = $capabilities;
    }

    public function id(): WebhostingPackageId
    {
        return $this->packageId;
    }

    public function capabilities(): Capabilities
    {
        return $this->capabilities;
    }
}
