<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Organization;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use ParkManager\Domain\User\User;

#[Entity]
#[Table(name: 'organization_member')]
class OrganizationMember
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'cascade')]
        public User $user,

        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'members')]
        #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'cascade')]
        public Organization $organization,

        #[Column(name: 'access_level', enumType: AccessLevel::class)]
        public AccessLevel $accessLevel = AccessLevel::LEVEL_MANAGER
    ) {}

    public function changeAccessLevel(AccessLevel $accessLevel): void
    {
        $this->accessLevel = $accessLevel;
    }
}
