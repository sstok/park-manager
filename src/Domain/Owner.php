<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Lifthill\Component\Common\Domain\Attribute\Entity as DomainEntity;
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
 */
#[Entity]
#[Table(name: 'entity_owner')]
#[DomainEntity]
class Owner implements \Stringable
{
    #[Id]
    #[Column(name: 'owner_id', type: OwnerId::class)]
    #[GeneratedValue(strategy: 'NONE')]
    public OwnerId $id;

    private function __construct(
        #[ORM\OneToOne(targetEntity: User::class, fetch: 'EAGER')]
        #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', unique: true, nullable: true, onDelete: 'CASCADE')]
        private ?User $user = null,

        #[ORM\OneToOne(targetEntity: Organization::class, fetch: 'EAGER')]
        #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', unique: true, nullable: true, onDelete: 'CASCADE')]
        private ?Organization $organization = null
    ) {
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

    public function getId(): OrganizationId | UserId
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

    public function getLinkedEntity(): Organization | User
    {
        return $this->user ?? $this->organization;
    }
}
