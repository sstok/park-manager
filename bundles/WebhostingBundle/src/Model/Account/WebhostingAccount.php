<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Model\Account;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use ParkManager\Bundle\CoreBundle\Model\DomainEventsCollectionTrait;
use ParkManager\Bundle\CoreBundle\Model\OwnerId;
use ParkManager\Bundle\CoreBundle\Model\RecordsDomainEvents;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Capabilities;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlan;

/**
 * @ORM\Entity()
 * @ORM\Table(name="account", schema="webhosting")
 */
class WebhostingAccount implements RecordsDomainEvents
{
    use DomainEventsCollectionTrait;

    /**
     * @ORM\Id()
     * @ORM\Column(type="park_manager_webhosting_account_id")
     * @ORM\GeneratedValue(strategy="NONE")
     *
     * @var WebhostingAccountId
     */
    protected $id;

    /**
     * The WebhostingPlan is null for an exclusive webhosting plan.
     *
     * @ORM\ManyToOne(targetEntity="ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlan")
     * @ORM\JoinColumn(nullable=true, name="plan_id", referencedColumnName="id", onDelete="RESTRICT")
     *
     * @var WebhostingPlan|null
     */
    protected $plan;

    /**
     * @ORM\Column(type="webhosting_capabilities")
     *
     * @var Capabilities
     */
    protected $capabilities;

    /**
     * @ORM\Column(name="owner_id", type="park_manager_owner_id")
     *
     * @var OwnerId
     */
    protected $owner;

    /**
     * @ORM\Column(name="expires_on", type="datetime_immutable", nullable=true)
     *
     * @var DateTimeImmutable|null
     */
    protected $expirationDate;

    /**
     * @ORM\Column(name="marked_for_removal", type="boolean", nullable=true)
     *
     * @var bool
     */
    private $markedForRemoval = false;

    protected function __construct(WebhostingAccountId $id, OwnerId $owner)
    {
        $this->id    = $id;
        $this->owner = $owner;
    }

    public static function register(WebhostingAccountId $id, OwnerId $owner, WebhostingPlan $plan): self
    {
        $account = new static($id, $owner);
        // Store the capabilities as part of the webhosting account
        // The plan can be changed at any time, but the capabilities are immutable.
        $account->capabilities = $plan->capabilities();
        $account->plan      = $plan;
        $account->recordThat(new Event\WebhostingAccountWasRegistered($id, $owner));

        return $account;
    }

    public static function registerWithCustomCapabilities(WebhostingAccountId $id, OwnerId $owner, Capabilities $capabilities): self
    {
        $account               = new static($id, $owner);
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

    public function plan(): ?WebhostingPlan
    {
        return $this->plan;
    }

    public function capabilities(): Capabilities
    {
        return $this->capabilities;
    }

    /**
     * Change the webhosting plan assignment, withing using
     * the plan Capabilities of the webhosting plan.
     */
    public function assignPlan(WebhostingPlan $plan): void
    {
        if ($plan === $this->plan) {
            return;
        }

        $this->plan = $plan;
        $this->recordThat(new Event\WebhostingAccountPlanAssignmentWasChanged($this->id, $plan));
    }

    /**
     * Change the webhosting plan assignment, and
     * set the Capabilities of the webhosting plan.
     */
    public function assignPlanWithCapabilities(WebhostingPlan $plan): void
    {
        if ($plan === $this->plan && $this->capabilities->equals($plan->capabilities())) {
            return;
        }

        $this->plan      = $plan;
        $this->capabilities = $plan->capabilities();
        $this->recordThat(Event\WebhostingAccountPlanAssignmentWasChanged::withCapabilities($this->id, $plan));
    }

    /**
     * Change the webhosting account Capabilities.
     *
     * This removes the plan assignment and makes the account's
     * Capabilities exclusive.
     */
    public function assignCustomCapabilities(Capabilities $capabilities): void
    {
        if ($this->plan === null && $this->capabilities->equals($capabilities)) {
            return;
        }

        $this->plan      = null;
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
    public function setExpirationDate(DateTimeImmutable $data): void
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

    public function isExpired(?DateTimeImmutable $current = null): bool
    {
        if ($this->expirationDate === null) {
            return false;
        }

        return $this->expirationDate->getTimestamp() <= ($current ?? new DateTimeImmutable())->getTimestamp();
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
