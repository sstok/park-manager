<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Space;

use Assert\Assertion;
use Assert\InvalidArgumentException as AssertionInvalidArgumentException;
use Carbon\Carbon;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use ParkManager\Domain\ByteSize;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\Owner;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\Plan;

/**
 * @ORM\Entity
 * @ORM\Table(name="space")
 */
class Space
{
    /**
     * @ORM\Id
     * @ORM\Column(type="park_manager_webhosting_space_id")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public SpaceId $id;

    /**
     * @ORM\ManyToOne(targetEntity=Plan::class)
     * @ORM\JoinColumn(nullable=true, name="plan_id", referencedColumnName="id", onDelete="RESTRICT")
     */
    public ?Plan $plan = null;

    /**
     * READ-ONLY.
     *
     * @ORM\Embedded(class=Constraints::class, columnPrefix="constraint_")
     */
    public Constraints $constraints;

    /**
     * @ORM\ManyToOne(targetEntity=Owner::class)
     * @ORM\JoinColumn(name="owner", referencedColumnName="owner_id", onDelete="RESTRICT")
     */
    public Owner $owner;

    /**
     * @ORM\Column(name="expires_on", type="datetime_immutable", nullable=true)
     */
    protected ?\DateTimeImmutable $expirationDate = null;

    /**
     * @ORM\Column(name="marked_for_removal", type="boolean", nullable=true)
     */
    public bool $markedForRemoval = false;

    /**
     * READ-ONLY: The allocated size for web storage.
     *
     * This value must not exceed the Constraints.$storageSize value,
     * and must not be more than the total size allocated to mail storage.
     *
     * If this value is NULL the size is not allocated yet, and must be ignored
     * for any calculations.
     *
     * @ORM\Column(type="byte_size", nullable=true)
     */
    public ?ByteSize $webQuota = null;

    /**
     * READ-ONLY.
     *
     * This is a static value updated by `DomainName::transferToSpace(primary: true)`
     * meant for display purposes only.
     *
     * @ORM\Embedded(class=DomainNamePair::class, columnPrefix="primary_domain_")
     */
    public DomainNamePair $primaryDomainLabel;

    /**
     * @ORM\Column(name="status", type="park_manager_webhosting_space_status")
     */
    public SpaceStatus $status;

    private function __construct(SpaceId $id, Owner $owner, Constraints $constraints)
    {
        $this->id = $id;
        $this->owner = $owner;
        $this->status = SpaceStatus::get('Registered');

        // Store the constraints as part of the webhosting Space
        // the assigned constraints are immutable.
        $this->constraints = $constraints;
    }

    public static function register(SpaceId $id, Owner $owner, Plan $plan): self
    {
        $space = new self($id, $owner, $plan->constraints);
        $space->plan = $plan;

        return $space;
    }

    public static function registerWithCustomConstraints(SpaceId $id, Owner $owner, Constraints $constraints): self
    {
        return new self($id, $owner, $constraints);
    }

    public function getAssignedPlan(): ?Plan
    {
        return $this->plan;
    }

    /**
     * Change the webhosting Plan assignment,
     * withing using the actual Constraints of the set.
     */
    public function assignPlan(Plan $plan): void
    {
        $this->plan = $plan;
    }

    /**
     * Change the webhosting Plan assignment,
     * and use the Constraints of the assigned plan.
     */
    public function assignPlanWithConstraints(Plan $plan, Constraints $constraints): void
    {
        $this->plan = $plan;

        if (! $this->constraints->equals($constraints)) {
            $this->constraints = $constraints;
        }
    }

    /**
     * Change the webhosting space Constraints.
     *
     * This removes the plan's assignment and makes the space's
     * Constraints exclusive.
     */
    public function assignCustomConstraints(Constraints $constraints): void
    {
        $this->plan = null;

        if (! $this->constraints->equals($constraints)) {
            $this->constraints = $constraints;
        }
    }

    /**
     * Resetting to NULL is not possible, use {ByteSize::Inf()} instead.
     *
     * @throws AssertionInvalidArgumentException
     */
    public function setWebQuota(ByteSize $size): void
    {
        Assertion::false($size->greaterThan($this->constraints->storageSize), 'WebSpace quota cannot be greater than the total storage size.', 'webQuota');

        if ($size->equals($this->webQuota)) {
            return;
        }

        $this->webQuota = $size;
    }

    public function switchToOwner(Owner $owner): void
    {
        $this->owner = $owner;
    }

    /**
     * Set the webhosting space to expire (be removed) on a specific
     * datetime.
     *
     * Note: There is no promise the webhosting space will in fact
     * be removed on the specified date. This depends on other subsystems.
     */
    public function markExpirationDate(DateTimeImmutable $data): void
    {
        Assertion::false(Carbon::instance($data)->isPast(), 'Expiration date cannot be in the past.', 'expirationDate');

        $this->expirationDate = $data;
    }

    /**
     * Remove the webhosting space's expiration date (if any).
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

        return $this->expirationDate->getTimestamp() <= ($current ?? new Carbon())->getTimestamp();
    }

    /**
     * Mark the webhosting space for removal (this cannot be undone!).
     */
    public function markForRemoval(): void
    {
        $this->markedForRemoval = true;
    }

    public function isMarkedForRemoval(): bool
    {
        return $this->markedForRemoval;
    }

    public function setPrimaryDomainLabel(DomainNamePair $primaryDomainLabel): void
    {
        $this->primaryDomainLabel = $primaryDomainLabel;
    }

    public function assignStatus(SpaceStatus $newStatus): void
    {
        if ($this->status->equals($newStatus)) {
            return;
        }

        SpaceStatus::validateNewStatus($this->status, $newStatus);

        $this->status = $newStatus;
    }

    public function getStatusAsString(): string
    {
        if ($this->markedForRemoval) {
            return 'marked_for_removal';
        }

        return $this->status->label();
    }
}
