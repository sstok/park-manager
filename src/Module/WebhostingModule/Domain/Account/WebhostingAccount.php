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

namespace ParkManager\Module\WebhostingModule\Domain\Account;

use ParkManager\Module\CoreModule\Domain\EventsRecordingEntity;
use ParkManager\Module\CoreModule\Domain\Shared\OwnerId;
use ParkManager\Module\WebhostingModule\Domain\Package\Capabilities;
use ParkManager\Module\WebhostingModule\Domain\Package\WebhostingPackage;

class WebhostingAccount extends EventsRecordingEntity
{
    /**
     * The WebhostingPackage is null for an exclusive webhosting package.
     *
     * @var WebhostingPackage|null
     */
    protected $package;

    /**
     * @var Capabilities
     */
    protected $capabilities;

    /**
     * @var WebhostingAccountId
     */
    protected $id;

    /**
     * @var OwnerId
     */
    protected $owner;

    /**
     * @var \DateTimeImmutable|null
     */
    protected $expirationDate;

    private $markedForRemoval = false;

    protected function __construct(WebhostingAccountId $id, OwnerId $owner)
    {
        $this->id = $id;
        $this->owner = $owner;
    }

    public static function register(WebhostingAccountId $id, OwnerId $owner, WebhostingPackage $package): self
    {
        $account = new static($id, $owner);
        // Store the capabilities as part of the webhosting account
        // The package can be changed at any time, but the capabilities are immutable.
        $account->capabilities = $package->capabilities();
        $account->package = $package;
        $account->recordThat(new Event\WebhostingAccountWasRegistered($id, $owner));

        return $account;
    }

    public static function registerWithCustomCapabilities(WebhostingAccountId $id, OwnerId $owner, Capabilities $capabilities): self
    {
        $account = new static($id, $owner);
        $account->capabilities = $capabilities;
        $account->recordThat(new Event\WebhostingAccountWasRegistered($id, $owner));

        return $account;
    }

    public function id(): WebhostingAccountId
    {
        return $this->id;
    }

    public function owner(): OwnerId
    {
        return $this->owner;
    }

    public function package(): ?WebhostingPackage
    {
        return $this->package;
    }

    public function capabilities(): Capabilities
    {
        return $this->capabilities;
    }

    /**
     * Change the webhosting package assignment, withing using
     * the package Capabilities of the webhosting package.
     */
    public function assignPackage(WebhostingPackage $package): void
    {
        if ($package === $this->package) {
            return;
        }

        $this->package = $package;
        $this->recordThat(new Event\WebhostingAccountPackageAssignmentWasChanged($this->id, $package));
    }

    /**
     * Change the webhosting package assignment, and
     * set the Capabilities of the webhosting package.
     */
    public function assignPackageWithCapabilities(WebhostingPackage $package): void
    {
        if ($package === $this->package && $this->capabilities->equals($package->capabilities())) {
            return;
        }

        $this->package = $package;
        $this->capabilities = $package->capabilities();
        $this->recordThat(Event\WebhostingAccountPackageAssignmentWasChanged::withCapabilities($this->id, $package));
    }

    /**
     * Change the webhosting account Capabilities.
     *
     * This removes the package assignment and makes the account's
     * Capabilities exclusive.
     */
    public function assignCustomCapabilities(Capabilities $capabilities): void
    {
        if (null === $this->package && $this->capabilities->equals($capabilities)) {
            return;
        }

        $this->package = null;
        $this->capabilities = $capabilities;
        $this->recordThat(new Event\WebhostingAccountCapabilitiesWasChanged($this->id, $capabilities));
    }

    public function switchOwner(OwnerId $owner): void
    {
        if ($this->owner->equals($owner)) {
            return;
        }

        $this->recordThat(new Event\WebhostingAccountOwnerWasSwitched($this->id, $this->owner, $owner));
        $this->owner = $owner;
    }

    /**
     * Set the webhosting account to expire (be removed) on a specific
     * datetime.
     *
     * Note: There is no promise the webhosting account will in fact
     * be removed on the specified date. This depends on other subsystems.
     */
    public function setExpirationDate(\DateTimeImmutable $data): void
    {
        $this->expirationDate = $data;
    }

    /**
     * Remove the webhosting account's expiration date (if any).
     */
    public function removeExpirationDate(): void
    {
        $this->expirationDate = null;
    }

    public function isExpired(?\DateTimeImmutable $current = null): bool
    {
        if (null === $this->expirationDate) {
            return false;
        }

        return $this->expirationDate->getTimestamp() <= ($current ?? new \DateTimeImmutable())->getTimestamp();
    }

    /**
     * Mark the webhosting account for removal (this cannot be undone!).
     */
    public function markForRemoval(): void
    {
        if ($this->markedForRemoval) {
            return;
        }

        $this->markedForRemoval = true;
        $this->recordThat(new Event\WebhostingAccountWasMarkedForRemoval($this->id));
    }

    public function isMarkedForRemoval(): bool
    {
        return $this->markedForRemoval;
    }
}
