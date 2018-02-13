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
use ParkManager\Component\Model\RootEntityOwner;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccountId;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class WebhostingAccountWasRegistered extends DomainEvent
{
    private $accountId;
    private $owner;

    public function __construct(WebhostingAccountId $id, RootEntityOwner $owner)
    {
        $this->accountId = $id;
        $this->owner = $owner;
    }

    public function id(): WebhostingAccountId
    {
        return $this->accountId;
    }

    public function owner(): RootEntityOwner
    {
        return $this->owner;
    }
}
