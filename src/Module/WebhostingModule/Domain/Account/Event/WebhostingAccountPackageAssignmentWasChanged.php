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

namespace ParkManager\Module\WebhostingModule\Domain\Account\Event;

use ParkManager\Component\DomainEvent\DomainEvent;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountId;
use ParkManager\Module\WebhostingModule\Domain\Package\Capabilities;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackage;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackageId;

final class WebhostingAccountPackageAssignmentWasChanged extends DomainEvent
{
    private $accountId;
    private $package;

    /**
     * @var Capabilities|null
     */
    private $capabilities;

    public function __construct(WebhostingAccountId $id, WebhostingPackage $package)
    {
        $this->accountId = $id;
        $this->package = $package->id();
    }

    public static function withCapabilities(WebhostingAccountId $id, WebhostingPackage $package): self
    {
        $event = new self($id, $package);
        $event->capabilities = $package->capabilities();

        return $event;
    }

    public function id(): WebhostingAccountId
    {
        return $this->accountId;
    }

    public function package(): WebhostingPackageId
    {
        return $this->package;
    }

    public function capabilities(): ?Capabilities
    {
        return $this->capabilities;
    }
}
