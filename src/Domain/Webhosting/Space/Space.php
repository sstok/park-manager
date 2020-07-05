<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Space;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use ParkManager\Domain\User\User;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\SharedConstraintSet;

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
     * @ORM\ManyToOne(targetEntity=SharedConstraintSet::class)
     * @ORM\JoinColumn(nullable=true, name="constraint_set_ref", referencedColumnName="id", onDelete="RESTRICT")
     *
     * @var SharedConstraintSet|null
     */
    public $constraintSet;

    /**
     * @ORM\Column(name="assigned_constraints_ref", type="webhosting_constraints")
     */
    protected Constraints $constraints;

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

    protected function __construct(SpaceId $id, ?User $owner)
    {
        $this->id = $id;
        $this->owner = $owner;
    }

    public static function register(SpaceId $id, ?User $owner, SharedConstraintSet $constraintSet): self
    {
        $space = new self($id, $owner);
        // Store the constraints as part of the webhosting space
        // the assigned constraints are immutable.
        $space->constraints = $constraintSet->getConstraints();
        $space->constraintSet = $constraintSet;

        return $space;
    }

    public static function registerWithCustomConstraints(SpaceId $id, ?User $owner, Constraints $constraints): self
    {
        $space = new self($id, $owner);
        $space->constraints = $constraints;

        return $space;
    }

    public function getId(): SpaceId
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function getAssignedConstraintSet(): ?SharedConstraintSet
    {
        return $this->constraintSet;
    }

    public function getConstraints(): Constraints
    {
        return $this->constraints;
    }

    /**
     * Change the webhosting SharedConstraintSet assignment,
     * withing using the actual Constraints of the set.
     */
    public function assignConstraintSet(SharedConstraintSet $constraintSet): void
    {
        $this->constraintSet = $constraintSet;
    }

    /**
     * Change the webhosting SharedConstraintSet assignment,
     * and use the Constraints of the assigned set.
     */
    public function assignSetWithConstraints(SharedConstraintSet $constraintSet): void
    {
        $this->constraintSet = $constraintSet;
        $this->constraints = $constraintSet->getConstraints();
    }

    /**
     * Change the webhosting space Constraints.
     *
     * This removes the set's assignment and makes the space's
     * Constraints exclusive.
     */
    public function assignCustomConstraints(Constraints $constraints): void
    {
        $this->constraintSet = null;
        $this->constraints = $constraints;
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
