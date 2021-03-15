<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Organization;

use Assert\Assertion;
use Doctrine\ORM\Mapping as ORM;
use ParkManager\Domain\User\User;

/**
 * @ORM\Entity
 * @ORM\Table(name="organization_member")
 */
class OrganizationMember
{
    /**
     * Has access to manage the organization details and members.
     */
    public const LEVEL_MANAGER = 1;

    /**
     * Has only (restricted) access to organization owned Spaces.
     */
    public const LEVEL_COLLABORATOR = 2;

    /**
     * @ORM\Id
     *
     * @ORM\ManyToOne(targetEntity=Organization::class, inversedBy="members")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="cascade")
     */
    public Organization $organization;

    /**
     * @ORM\Id
     *
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="cascade")
     */
    public User $user;

    /**
     * @ORM\Column(name="access_level", type="integer")
     */
    public int $accessLevel;

    public function __construct(User $user, Organization $organization, int $level = self::LEVEL_MANAGER)
    {
        $this->organization = $organization;
        $this->user = $user;

        $this->changeAccessLevel($level);
    }

    public function changeAccessLevel(int $accessLevel): void
    {
        Assertion::between($accessLevel, 1, 2, 'Access-level must be either LEVEL_MANAGER or LEVEL_COLLABORATOR', 'level');

        $this->accessLevel = $accessLevel;
    }
}
