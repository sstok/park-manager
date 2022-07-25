<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Organization;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use ParkManager\Domain\Organization\Exception\OrganizationMemberNotFound;
use ParkManager\Domain\TimestampableTrait;
use ParkManager\Domain\User\User;

/**
 * An Organization is a shared "identity" of users that may be assigned
 * as owner instead of a single user.
 */
#[Entity]
#[Table(name: 'organization')]
class Organization
{
    use TimestampableTrait;

    /**
     * @var Collection<int, OrganizationMember>
     */
    #[OneToMany(mappedBy: 'organization', targetEntity: OrganizationMember::class, cascade: ['ALL'])]
    public Collection $members;

    public function __construct(
        #[Id]
        #[Column(type: 'park_manager_organization_id')]
        #[GeneratedValue(strategy: 'NONE')]
        public OrganizationId $id,

        #[ORM\Column(name: 'name', type: 'string')]
        public string $name
    ) {
        $this->members = new ArrayCollection();
    }

    /**
     * Add a User as member of the organization.
     *
     * When the User is already a member their access-level
     * is updated instead.
     */
    public function addMember(User $user, AccessLevel $level = AccessLevel::LEVEL_MANAGER): void
    {
        /** @var OrganizationMember|null $member */
        [$member, $memberId] = $this->findMembership($user);

        if ($member !== null) {
            if ($member->accessLevel !== $level) {
                $member->changeAccessLevel($level);

                $this->members->set($memberId, $member);
            }
        } else {
            $this->members->add(new OrganizationMember($user, $this, $level));
        }
    }

    /**
     * @return array{0: OrganizationMember|null, 1: int}
     */
    private function findMembership(User $user): array
    {
        $expression = Criteria::expr();
        /** @var Collection<int, OrganizationMember> $members */
        $members = $this->members->matching(new Criteria($expression->eq('user', $user)));

        if ($members->count() === 0) {
            return [null, 0];
        }

        /** @var OrganizationMember $member */
        $member = $members->first();
        $memberId = $members->key();

        \assert($memberId !== false && $memberId !== null);

        return [$member, $memberId];
    }

    /**
     * @throws OrganizationMemberNotFound
     */
    public function removeMember(User $user): void
    {
        [$member, $memberId] = $this->findMembership($user);

        if ($member === null) {
            throw OrganizationMemberNotFound::with($this->id, $user->id);
        }

        $this->members->remove($memberId);
    }

    /**
     * @throws OrganizationMemberNotFound
     */
    public function getMember(User $user): OrganizationMember
    {
        [$member,] = $this->findMembership($user);

        if ($member === null) {
            throw OrganizationMemberNotFound::with($this->id, $user->id);
        }

        return $member;
    }

    public function hasMember(User $user, ?AccessLevel $accessLevel = null): bool
    {
        /** @var OrganizationMember|null $member */
        [$member,] = $this->findMembership($user);

        if ($member === null) {
            return false;
        }

        if ($accessLevel === null) {
            return true;
        }

        return $member->accessLevel === $accessLevel;
    }

    /**
     * Returns whether the Organization is internal,
     * meaning it's managed by the application and cannot
     * be changed though the UI.
     *
     * For example the "Administrator Organization"
     * is used to group all Administrator users for collective
     * ownership.
     */
    public function isInternal(): bool
    {
        return $this->id->equals(OrganizationId::fromString(OrganizationId::ADMIN_ORG))
               || $this->id->equals(OrganizationId::fromString(OrganizationId::SYSTEM_APP));
    }
}
