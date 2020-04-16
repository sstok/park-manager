<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\DomainName;

use Doctrine\ORM\Mapping as ORM;
use ParkManager\Domain\Webhosting\DomainName;
use ParkManager\Domain\Webhosting\DomainName\Exception\CannotTransferPrimaryDomainName;
use ParkManager\Domain\Webhosting\Space\Space;

/**
 * @ORM\Entity
 * @ORM\Table(name="domain_name", schema="webhosting", indexes={
 *     @ORM\Index(name="domain_name_primary_marking_idx", columns={"space", "is_primary"}),
 * })
 */
class WebhostingDomainName
{
    /**
     * @ORM\Id
     * @ORM\Column(type="park_manager_webhosting_domain_name_id")
     * @ORM\GeneratedValue(strategy="NONE")
     *
     * @var WebhostingDomainNameId
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity=Space::class)
     * @ORM\JoinColumn(onDelete="CASCADE", name="space", referencedColumnName="id")
     *
     * @var Space
     */
    protected $space;

    /**
     * @ORM\Embedded(class=DomainName::class, columnPrefix="domain_")
     *
     * @var DomainName
     */
    protected $domainName;

    /**
     * @ORM\Column(name="is_primary", type="boolean")
     *
     * @var bool
     */
    protected $primary = false;

    public function __construct(Space $space, DomainName $domainName)
    {
        $this->space = $space;
        $this->domainName = $domainName;
        $this->id = WebhostingDomainNameId::create();
    }

    public static function registerPrimary(Space $space, DomainName $domainName): self
    {
        $instance = new self($space, $domainName);
        $instance->primary = true;

        return $instance;
    }

    public static function registerSecondary(Space $space, DomainName $domainName): self
    {
        return new self($space, $domainName);
    }

    public function getId(): WebhostingDomainNameId
    {
        return $this->id;
    }

    public function getDomainName(): DomainName
    {
        return $this->domainName;
    }

    public function getSpace(): Space
    {
        return $this->space;
    }

    public function markPrimary(): void
    {
        $this->primary = true;
    }

    public function isPrimary(): bool
    {
        return $this->primary;
    }

    public function transferToSpace(Space $space): void
    {
        // It's still possible the primary marking was given directly before
        // issuing the transfer, meaning the primary marking was not persisted
        // yet for the old owner. But checking this further is not worth it.
        if ($this->isPrimary()) {
            throw CannotTransferPrimaryDomainName::of($this->id, $this->space->getId(), $space->getId());
        }

        $this->space = $space;
    }

    public function changeName(DomainName $domainName): void
    {
        $this->domainName = $domainName;
    }
}
