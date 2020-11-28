<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\DomainName;

use Doctrine\ORM\Mapping as ORM;
use ParkManager\Domain\DomainName\Exception\CannotAssignDomainNameWithDifferentOwner;
use ParkManager\Domain\DomainName\Exception\CannotTransferPrimaryDomainName;
use ParkManager\Domain\User\User;
use ParkManager\Domain\Webhosting\Space\Space;

/**
 * @ORM\Entity
 * @ORM\Table(name="domain_name", indexes={
 *     @ORM\Index(name="domain_name_primary_marking_idx", columns={"space", "is_primary"}),
 * })
 */
class DomainName
{
    /**
     * READ-ONLY.
     *
     * @ORM\Id
     * @ORM\Column(type="park_manager_domain_name_id")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public DomainNameId $id;

    /**
     * READ-ONLY.
     *
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(name="owner", nullable=true, fieldName="id")
     */
    public ?User $owner = null;

    /**
     * READ-ONLY.
     *
     * @ORM\ManyToOne(targetEntity=Space::class)
     * @ORM\JoinColumn(onDelete="CASCADE", name="space", referencedColumnName="id")
     */
    public ?Space $space = null;

    /**
     * READ-ONLY.
     *
     * @ORM\Embedded(class=DomainNamePair::class, columnPrefix="domain_")
     */
    public DomainNamePair $namePair;

    /**
     * READ-ONLY.
     *
     * @ORM\Column(name="is_primary", type="boolean")
     */
    public bool $primary = false;

    private function __construct(DomainNameId $id, DomainNamePair $domainName)
    {
        $this->namePair = $domainName;
        $this->id = $id;
    }

    public static function register(DomainNameId $id, DomainNamePair $domainName, ?User $owner): self
    {
        $instance = new self($id, $domainName);
        $instance->owner = $owner;

        return $instance;
    }

    public static function registerForSpace(DomainNameId $id, Space $space, DomainNamePair $domainName): self
    {
        $instance = new self($id, $domainName);
        $instance->space = $space;
        $instance->primary = true;

        return $instance;
    }

    public static function registerSecondaryForSpace(DomainNameId $id, Space $space, DomainNamePair $domainName): self
    {
        $instance = new self($id, $domainName);
        $instance->space = $space;

        return $instance;
    }

    public function toString(): string
    {
        return $this->namePair->toString();
    }

    public function markPrimary(): void
    {
        $this->primary = true;
    }

    public function isPrimary(): bool
    {
        return $this->primary;
    }

    public function transferToSpace(Space $space, bool $primary = false): void
    {
        // This path is not the correct way, but because no constraints are
        // breached it should not result in an exception.
        if ($this->space === $space) {
            if ($primary) {
                $this->markPrimary();
            }

            return;
        }

        // It's still possible the primary marking was given directly before
        // issuing the transfer, meaning the primary marking was not persisted
        // yet for the old owner. But checking this further is not worth it.
        if ($this->space !== null) {
            if ($this->isPrimary()) {
                throw new CannotTransferPrimaryDomainName($this->namePair, $this->space->id, $space->id);
            }

            if ($this->space->owner !== $space->owner) {
                throw new CannotAssignDomainNameWithDifferentOwner($this->namePair, $this->space->id, $space->id);
            }
        } elseif ($this->owner !== $space->owner) {
            throw new CannotAssignDomainNameWithDifferentOwner($this->namePair, null, $space->id);
        }

        $this->space = $space;
        $this->primary = $primary;

        // Remove the ownership relation to reduce the need for synchronizing.
        $this->owner = null;
    }

    /**
     * Transfers the DomainName ownership to a User and removes the Space assignment.
     */
    public function transferToOwner(?User $newOwner): void
    {
        if ($this->space !== null && $this->isPrimary()) {
            throw new CannotTransferPrimaryDomainName($this->namePair, $this->space->id, null);
        }

        $this->space = null;
        $this->primary = true;
        $this->owner = $newOwner;
    }
}
