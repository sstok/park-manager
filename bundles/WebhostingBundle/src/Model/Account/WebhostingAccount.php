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
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraints;
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
     * @ORM\Column(name="plan_constraints", type="webhosting_plan_constraints")
     *
     * @var Constraints
     */
    protected $planConstraints;

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
        // Store the constraints as part of the webhosting account
        // The plan can be changed at any time, but the constraints are immutable.
        $account->planConstraints = $plan->constraints();
        $account->plan            = $plan;
        $account->recordThat(new Event\WebhostingAccountWasRegistered($id, $owner));

        return $account;
    }

    public static function registerWithCustomConstraints(WebhostingAccountId $id, OwnerId $owner, Constraints $constraints): self
    {
        $account                  = new static($id, $owner);
        $account->planConstraints = $constraints;
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

    public function planConstraints(): Constraints
    {
        return $this->planConstraints;
    }

    /**
     * Change the webhosting plan assignment, withing using
     * the plan Constraints of the webhosting plan.
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
     * set the Constraints of the webhosting plan.
     */
    public function assignPlanWithConstraints(WebhostingPlan $plan): void
    {
        if ($plan === $this->plan && $this->planConstraints->equals($plan->constraints())) {
            return;
        }

        $this->plan            = $plan;
        $this->planConstraints = $plan->constraints();
        $this->recordThat(Event\WebhostingAccountPlanAssignmentWasChanged::withConstraints($this->id, $plan));
    }

    /**
     * Change the webhosting account Constraints.
     *
     * This removes the plan assignment and makes the account's
     * Constraints exclusive.
     */
    public function assignCustomConstraints(Constraints $constraints): void
    {
        if ($this->plan === null && $this->planConstraints->equals($constraints)) {
            return;
        }

        $this->plan            = null;
        $this->planConstraints = $constraints;
        $this->recordThat(new Event\WebhostingAccountPlanConstraintsWasChanged($this->id, $constraints));
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
