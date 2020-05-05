<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\DomainName;

use Doctrine\ORM\Mapping as ORM;
use ParkManager\Domain\User\User;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="domain_name",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="domain_name_uniq", columns={"name_part", "tld"}),
 *     }
 * )
 */
class DomainName
{
    /**
     * @ORM\Id
     * @ORM\Column(type="park_manager_domain_name_id")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public DomainNameId $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(name="owner", nullable=true, fieldName="id")
     */
    public ?User $owner = null;

    /**
     * @ORM\Column(type="text", name="name_part")
     */
    public string $name;

    /**
     * @ORM\Column(type="text")
     */
    public string $tld;

    public function __construct(DomainNameId $id, ?User $owner, string $name, string $tld)
    {
        $this->id = $id;
        $this->owner = $owner;
        $this->name = $name;
        $this->tld = $tld;
    }

    public function changeOwner(?User $owner): void
    {
        $this->owner = $owner;
    }
}
