<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\DomainName;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Lifthill\Component\Common\Domain\Attribute\Entity as DomainEntity;
use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParkManager\Domain\DomainName\Exception\CannotAssignDomainNameWithDifferentOwner;
use ParkManager\Domain\DomainName\Exception\CannotTransferPrimaryDomainName;
use ParkManager\Domain\Owner;
use ParkManager\Domain\TimestampableTrait;
use ParkManager\Domain\Webhosting\Space\Space;

#[Entity]
#[Table(name: 'domain_name')]
#[Index(fields: ['space', 'primary'], name: 'domain_name_primary_marking_idx')]
#[DomainEntity]
class DomainName implements \Stringable
{
    use TimestampableTrait;

    #[ManyToOne(targetEntity: Owner::class)]
    #[JoinColumn(name: 'owner', referencedColumnName: 'owner_id', nullable: true)]
    public null | Owner $owner = null;

    #[ManyToOne(targetEntity: Space::class)]
    #[JoinColumn(name: 'space', referencedColumnName: 'id', onDelete: 'CASCADE')]
    public null | Space $space = null;

    #[Column(name: 'is_primary', type: 'boolean')]
    public bool $primary = false;

    private function __construct(
        #[Id]
        #[Column(type: 'park_manager_domain_name_id')]
        #[GeneratedValue(strategy: 'NONE')]
        public DomainNameId $id,

        #[ORM\Embedded(class: DomainNamePair::class, columnPrefix: 'domain_')]
        public DomainNamePair $namePair
    ) {}

    public static function register(DomainNameId $id, DomainNamePair $domainName, Owner $owner): self
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

        $space->setPrimaryDomainLabel($instance->namePair);

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

    public function toTruncatedString(int $length = 27, string $ellipsis = '[...]'): string
    {
        return $this->namePair->toTruncatedString($length, $ellipsis);
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
                $space->setPrimaryDomainLabel($this->namePair);
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
                throw CannotAssignDomainNameWithDifferentOwner::fromSpace($this->namePair, $this->space->id, $space->id);
            }
        } elseif ($this->owner !== $space->owner) {
            throw CannotAssignDomainNameWithDifferentOwner::toSpace($this->namePair, $space->id);
        }

        $this->space = $space;
        $this->primary = $primary;

        if ($primary) {
            $space->setPrimaryDomainLabel($this->namePair);
        }

        // Remove the ownership relation to reduce the need for synchronizing.
        $this->owner = null;
    }

    /**
     * Transfers the DomainName ownership to Owner and removes the Space assignment.
     */
    public function transferToOwner(Owner $newOwner): void
    {
        if ($this->space !== null && $this->isPrimary()) {
            throw new CannotTransferPrimaryDomainName($this->namePair, $this->space->id, null);
        }

        $this->space = null;
        $this->primary = true;
        $this->owner = $newOwner;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
