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
use ParkManager\Bundle\CoreBundle\Model\OwnerId;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraints;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlan;

/**
 * @ORM\Entity
 * @ORM\Table(name="account", schema="webhosting")
 */
class WebhostingAccount
{
    /**
     * @ORM\Id
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
        $this->id = $id;
        $this->owner = $owner;
    }

    public static function register(WebhostingAccountId $id, OwnerId $owner, WebhostingPlan $plan): self
    {
        $account = new static($id, $owner);
        // Store the constraints as part of the webhosting account
        // The plan can be changed at any time, but the constraints are immutable.
        $account->planConstraints = $plan->getConstraints();
        $account->plan = $plan;

        return $account;
    }

    public static function registerWithCustomConstraints(WebhostingAccountId $id, OwnerId $owner, Constraints $constraints): self
    {
        $account = new static($id, $owner);
        $account->planConstraints = $constraints;

        return $account;
    }

    public function getId(): WebhostingAccountId
    {
        return $this->id;
    }

    public function getOwner(): OwnerId
    {
        return $this->owner;
    }

    public function getPlan(): ?WebhostingPlan
    {
        return $this->plan;
    }

    public function getPlanConstraints(): Constraints
    {
        return $this->planConstraints;
    }

    /**
     * Change the webhosting plan assignment, withing using
     * the plan Constraints of the webhosting plan.
     */
    public function assignPlan(WebhostingPlan $plan): void
    {
        $this->plan = $plan;
    }

    /**
     * Change the webhosting plan assignment, and
     * set the Constraints of the webhosting plan.
     */
    public function assignPlanWithConstraints(WebhostingPlan $plan): void
    {
        $this->plan = $plan;
        $this->planConstraints = $plan->getConstraints();
    }

    /**
     * Change the webhosting account Constraints.
     *
     * This removes the plan assignment and makes the account's
     * Constraints exclusive.
     */
    public function assignCustomConstraints(Constraints $constraints): void
    {
        $this->plan = null;
        $this->planConstraints = $constraints;
    }

    public function switchOwner(OwnerId $owner): void
    {
        if ($this->owner->equals($owner)) {
            return;
        }

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
        $this->markedForRemoval = true;
    }

    public function isMarkedForRemoval(): bool
    {
        return $this->markedForRemoval;
    }
}
