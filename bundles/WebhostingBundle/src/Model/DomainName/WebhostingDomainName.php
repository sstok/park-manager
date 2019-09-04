<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Model\DomainName;

use Doctrine\ORM\Mapping as ORM;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccount;
use ParkManager\Bundle\WebhostingBundle\Model\DomainName;
use ParkManager\Bundle\WebhostingBundle\Model\DomainName\Exception\CannotTransferPrimaryDomainName;

/**
 * @ORM\Entity
 * @ORM\Table(name="domain_name", schema="webhosting", indexes={
 *     @ORM\Index(name="domain_name_primary_marking_idx", columns={"account", "is_primary"}),
 * }
 * )
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
     * @ORM\ManyToOne(targetEntity="ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccount")
     * @ORM\JoinColumn(onDelete="CASCADE", name="account", referencedColumnName="id")
     *
     * @var WebhostingAccount
     */
    protected $account;

    /**
     * @ORM\Embedded(class="ParkManager\Bundle\WebhostingBundle\Model\DomainName", columnPrefix="domain_")
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

    public function __construct(WebhostingAccount $account, DomainName $domainName)
    {
        $this->account = $account;
        $this->domainName = $domainName;
        $this->id = WebhostingDomainNameId::create();
    }

    /**
     * @return static
     */
    public static function registerPrimary(WebhostingAccount $account, DomainName $domainName)
    {
        $instance = new static($account, $domainName);
        $instance->primary = true;

        return $instance;
    }

    /**
     * @return static
     */
    public static function registerSecondary(WebhostingAccount $account, DomainName $domainName)
    {
        return new static($account, $domainName);
    }

    public function getId(): WebhostingDomainNameId
    {
        return $this->id;
    }

    public function getDomainName(): DomainName
    {
        return $this->domainName;
    }

    public function getAccount(): WebhostingAccount
    {
        return $this->account;
    }

    public function markPrimary(): void
    {
        $this->primary = true;
    }

    public function isPrimary(): bool
    {
        return $this->primary;
    }

    public function transferToAccount(WebhostingAccount $account): void
    {
        // It's still possible the primary marking was given directly before
        // issuing the transfer, meaning the primary marking was not persisted
        // yet for the old owner. But checking this further is not worth it.
        if ($this->isPrimary()) {
            throw CannotTransferPrimaryDomainName::of($this->id, $this->account->getId(), $account->getId());
        }

        $this->account = $account;
    }

    public function changeName(DomainName $domainName): void
    {
        $this->domainName = $domainName;
    }
}
