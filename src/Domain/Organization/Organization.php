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
use ParkManager\Domain\Organization\Exception\OrganizationMemberNotFound;
use ParkManager\Domain\TimestampableTrait;
use ParkManager\Domain\User\User;

/**
 * An Organization is a shared "identity" of users that may be assigned
 * as owner instead of a single user.
 *
 * @ORM\Entity
 * @ORM\Table(name="organization")
 */
class Organization
{
    use TimestampableTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="park_manager_organization_id")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public OrganizationId $id;

    /**
     * @ORM\Column(name="name", type="string")
     */
    public string $name;

    /**
     * @ORM\OneToMany(targetEntity=OrganizationMember::class, cascade={"ALL"}, mappedBy="organization")
     *
     * @var Collection<int, OrganizationMember>
     */
    public Collection $members;

    public function __construct(OrganizationId $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
        $this->members = new ArrayCollection();
    }

    /**
     * Add a User as member of the organization.
     *
     * When the User is already a member their access-level
     * is updated instead.
     */
    public function addMember(User $user, ?AccessLevel $level = null): void
    {
        $level ??= AccessLevel::get('LEVEL_MANAGER');

        /** @var OrganizationMember|null $member */
        [$member, $memberId] = $this->findMembership($user);

        if ($member !== null) {
            if (! $member->accessLevel->equals($level)) {
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

        \assert($memberId !== false);

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

        if ($accessLevel !== null) {
            return $member->accessLevel->equals($accessLevel);
        }

        return true;
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
        return $this->id->equals(OrganizationId::fromString(OrganizationId::ADMIN_ORG));
    }
}
