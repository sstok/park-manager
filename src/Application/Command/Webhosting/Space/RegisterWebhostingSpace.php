<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Space;

use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\OwnerId;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class RegisterWebhostingSpace
{
    /**
     * READ-ONLY.
     */
    public SpaceId $id;

    /**
     * READ-ONLY.
     */
    public DomainNamePair $domainName;

    /**
     * READ-ONLY.
     */
    public OwnerId $owner;

    /**
     * READ-ONLY.
     */
    public ?PlanId $planId = null;

    /**
     * READ-ONLY.
     */
    public ?Constraints $customConstraints = null;

    private function __construct(string $id, string $owner, DomainNamePair $domainName, ?string $planId, ?Constraints $constraints)
    {
        $this->id = SpaceId::fromString($id);
        $this->domainName = $domainName;
        $this->customConstraints = $constraints;
        $this->owner = OwnerId::fromString($owner);

        if ($planId !== null) {
            $this->planId = PlanId::fromString($planId);
        }
    }

    public static function withPlan(string $id, DomainNamePair $domainName, string $owner, string $planId): self
    {
        return new self($id, $owner, $domainName, $planId, null);
    }

    public static function withCustomConstraints(string $id, DomainNamePair $domainName, string $owner, Constraints $constraints): self
    {
        return new self($id, $owner, $domainName, null, $constraints);
    }
}
