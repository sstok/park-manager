<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Account;

use ParkManager\Domain\OwnerId;
use ParkManager\Domain\Webhosting\Account\WebhostingAccountId;
use ParkManager\Domain\Webhosting\DomainName;
use ParkManager\Domain\Webhosting\Plan\Constraints;
use ParkManager\Domain\Webhosting\Plan\WebhostingPlanId;

final class RegisterWebhostingAccount
{
    /**
     * READ-ONLY.
     *
     * @var WebhostingAccountId
     */
    public $id;

    /**
     * READ-ONLY.
     *
     * @var DomainName
     */
    public $domainName;

    /**
     * READ-ONLY.
     *
     * @var OwnerId
     */
    public $owner;

    /**
     * READ-ONLY.
     *
     * @var WebhostingPlanId|null
     */
    public $plan;

    /**
     * READ-ONLY.
     *
     * @var Constraints|null
     */
    public $customConstraints;

    private function __construct(string $id, string $owner, DomainName $domainName, ?string $planId, ?Constraints $constraints)
    {
        $this->id = WebhostingAccountId::fromString($id);
        $this->domainName = $domainName;
        $this->customConstraints = $constraints;
        $this->owner = OwnerId::fromString($owner);

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
}
