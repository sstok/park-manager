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

namespace ParkManager\Module\Webhosting\Model\Account\Command;

use ParkManager\Module\Webhosting\Model\Account\HasWebhostingAccountId;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccountOwner;
use ParkManager\Module\Webhosting\Model\DomainName;
use ParkManager\Module\Webhosting\Model\Package\Capabilities;
use ParkManager\Module\Webhosting\Model\Package\WebhostingPackageId;
use Prooph\Common\Messaging\Command;
use Prooph\Common\Messaging\PayloadTrait;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class RegisterWebhostingAccount extends Command
{
    use PayloadTrait;
    use HasWebhostingAccountId;

    /**
     * @var DomainName|null
     */
    private $domainName;

    /**
     * @var WebhostingAccountOwner|null
     */
    private $owner;

    /**
     * @var WebhostingPackageId|null
     */
    private $package;

    /**
     * @var Capabilities|null
     */
    private $capabilities;

    private function __construct(string $id, string $owner, DomainName $domainName, ?string $package, ?Capabilities $capabilities)
    {
        $this->init();
        $this->setPayload([
            'id' => $id,
            'owner' => $owner,
            'package' => $package,
            'capabilities' => null === $capabilities ? null : $capabilities->toArray(),
            'domain_name' => [$domainName->name(), $domainName->tld()],
        ]);

        $this->domainName = $domainName;
        $this->owner = WebhostingAccountOwner::fromString($owner);
        $this->capabilities = $capabilities;

        if (null !== $package) {
            $this->package = WebhostingPackageId::fromString($package);
        }
    }

    public static function withPackage(string $id, DomainName $domainName, string $owner, string $packageId): self
    {
        return new self($id, $owner, $domainName, $packageId, null);
    }

    public static function withCustomCapabilities(string $id, DomainName $domainName, string $owner, Capabilities $capabilities): self
    {
        return new self($id, $owner, $domainName, null, $capabilities);
    }

    public function owner(): WebhostingAccountOwner
    {
        if (null === $this->owner) {
            $this->owner = WebhostingAccountOwner::fromString($this->payload['owner']);
        }

        return $this->owner;
    }

    public function customCapabilities(): ?Capabilities
    {
        if (null === $this->capabilities && null !== $this->payload['capabilities']) {
            $this->capabilities = Capabilities::reconstituteFromArray($this->payload['capabilities']);
        }

        return $this->capabilities;
    }

    public function package(): ?WebhostingPackageId
    {
        if (null === $this->package && null !== $this->payload['package']) {
            $this->package = WebhostingPackageId::fromString($this->payload['package']);
        }

        return $this->package;
    }

    public function domainName(): DomainName
    {
        if (null === $this->domainName) {
            $this->domainName = new DomainName($this->payload['domain_name'][0], $this->payload['domain_name'][1]);
        }

        return $this->domainName;
    }
}
