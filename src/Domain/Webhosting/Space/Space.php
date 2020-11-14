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
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use ParkManager\Domain\ByteSize;
use ParkManager\Domain\User\User;
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
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=true, name="owner", referencedColumnName="id", onDelete="RESTRICT")
     */
    public ?User $owner = null;

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

    private function __construct(SpaceId $id, ?User $owner, Constraints $constraints)
    {
        $this->id = $id;
        $this->owner = $owner;

        // Store the constraints as part of the webhosting Space
        // the assigned constraints are immutable.
        $this->constraints = $constraints;
    }

    public static function register(SpaceId $id, ?User $owner, Plan $plan): self
    {
        $space = new self($id, $owner, $plan->constraints);
        $space->plan = $plan;

        return $space;
    }

    public static function registerWithCustomConstraints(SpaceId $id, ?User $owner, Constraints $constraints): self
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

    public function switchOwner(?User $owner): void
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
    public function setExpirationDate(DateTimeImmutable $data): void
    {
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

        return $this->expirationDate->getTimestamp() <= ($current ?? new DateTimeImmutable())->getTimestamp();
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
}
