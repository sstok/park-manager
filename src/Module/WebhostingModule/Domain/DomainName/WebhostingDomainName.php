<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Domain\DomainName;

use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccount;
use ParkManager\Module\WebhostingModule\Domain\DomainName;
use ParkManager\Module\WebhostingModule\Domain\DomainName\Exception\CannotTransferPrimaryDomainName;

class WebhostingDomainName
{
    /** @var WebhostingAccount */
    protected $account;

    /** @var DomainName */
    protected $domainName;

    /** @var bool */
    protected $primary = false;

    /** @var WebhostingDomainNameId */
    protected $id;

    public function __construct(WebhostingAccount $account, DomainName $domainName)
    {
        $this->account    = $account;
        $this->domainName = $domainName;
        $this->id         = WebhostingDomainNameId::create();
    }

    /**
     * @return static
     */
    public static function registerPrimary(WebhostingAccount $account, DomainName $domainName)
    {
        $instance          = new static($account, $domainName);
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

    public function id(): WebhostingDomainNameId
    {
        return $this->id;
    }

    public function domainName(): DomainName
    {
        return $this->domainName;
    }

    public function account(): WebhostingAccount
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
            throw CannotTransferPrimaryDomainName::of($this->id, $this->account->id(), $account->id());
        }

        $this->account = $account;
    }

    public function changeName(DomainName $domainName): void
    {
        $this->domainName = $domainName;
    }
}
