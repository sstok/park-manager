<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\UseCase\Account;

use ParkManager\Bundle\CoreBundle\Model\OwnerId;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountId;
use ParkManager\Bundle\WebhostingBundle\Model\DomainName;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraints;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\WebhostingPlanId;

final class RegisterWebhostingAccount
{
    private $id;
    private $domainName;
    private $owner;
    private $plan;
    private $constraints;

    private function __construct(string $id, string $owner, DomainName $domainName, ?string $planId, ?Constraints $constraints)
    {
        $this->id           = WebhostingAccountId::fromString($id);
        $this->domainName   = $domainName;
        $this->constraints = $constraints;
        $this->owner        = OwnerId::fromString($owner);

        if ($planId !== null) {
            $this->plan = WebhostingPlanId::fromString($planId);
        }
    }

    public static function withPlan(string $id, DomainName $domainName, string $owner, string $planId): self
    {
        return new self($id, $owner, $domainName, $planId, null);
    }

    public static function withCustomConstraints(string $id, DomainName $domainName, string $owner, Constraints $constraints): self
    {
        return new self($id, $owner, $domainName, null, $constraints);
    }

    public function id(): WebhostingAccountId
    {
        return $this->id;
    }

    public function owner(): OwnerId
    {
        return $this->owner;
    }

    public function customConstraints(): ?Constraints
    {
        return $this->constraints;
    }

    public function plan(): ?WebhostingPlanId
    {
        return $this->plan;
    }

    public function domainName(): DomainName
    {
        return $this->domainName;
    }
}
