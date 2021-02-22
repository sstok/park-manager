<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain;

use Doctrine\ORM\Mapping as ORM;
use ParkManager\Domain\Organization\Organization;
use ParkManager\Domain\Organization\OrganizationId;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;

/**
 * An Owner is either a `User` or `Organization` relationship-entity
 * to the actual entity. Immutable.
 *
 * The same ID as the linked entity is used as Owner-id.
 * Unlike a discriminator-mapping this doesn't produce any
 * overhead for the ORM process.
 *
 * @ORM\Entity
 * @ORM\Table(name="entity_owner")
 */
class Owner implements \Stringable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="park_manager_owner_id", name="owner_id")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public OwnerId $id;

    /**
     * @ORM\OneToOne(targetEntity=User::class, fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(nullable=true, referencedColumnName="id", name="user_id", onDelete="CASCADE", unique=true)
     */
    private ?User $user;

    /**
     * @ORM\OneToOne(targetEntity=Organization::class, fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(nullable=true, referencedColumnName="id", name="organization_id", onDelete="CASCADE", unique=true)
     */
    private ?Organization $organization;

    private function __construct(?User $user = null, ?Organization $organization = null)
    {
        $this->user = $user;
        $this->organization = $organization;

        // Internally the integrity is guarded by the relationship to Users and Organizations.
        // But we still need an ID for storage.
        $this->id = OwnerId::fromString($this->getId()->toString());
    }

    public static function byUser(User $user): self
    {
        return new self(user: $user);
    }

    public static function byOrganization(Organization $organization): self
    {
        return new self(organization: $organization);
    }

    public function getId(): UserId | OrganizationId
    {
        if (isset($this->user)) {
            return $this->user->id;
        }

        return $this->organization->id;
    }

    public function isUser(): bool
    {
        return isset($this->user);
    }

    public function isOrganization(): bool
    {
        return isset($this->organization);
    }

    public function __toString(): string
    {
        return $this->id->toString();
    }

    public function getLinkedEntity(): User | Organization
    {
        return $this->user ?? $this->organization;
    }
}
