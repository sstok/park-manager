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

namespace ParkManager\Module\Webhosting\Model\Account\Event;

use ParkManager\Component\Model\Event\DomainEvent;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccountId;
use ParkManager\Module\Webhosting\Model\Package\Capabilities;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class WebhostingAccountCapabilitiesWasChanged extends DomainEvent
{
    private $id;
    private $capabilities;

    public function __construct(WebhostingAccountId $id, Capabilities $capabilities)
    {
        $this->id = $id;
        $this->capabilities = $capabilities;
    }

    public function id(): WebhostingAccountId
    {
        return $this->id;
    }

    public function capabilities(): Capabilities
    {
        return $this->capabilities;
    }
}
