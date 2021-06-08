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
    private function __construct(
        public SpaceId $id,
        public OwnerId $owner,
        public DomainNamePair $domainName,
        public ?PlanId $planId = null,
        public ?Constraints $customConstraints = null
    ) {
    }

    public static function withPlan(string $id, DomainNamePair $domainName, string $owner, string $planId): self
    {
        return new self(SpaceId::fromString($id), OwnerId::fromString($owner), $domainName, PlanId::fromString($planId));
    }

    public static function withCustomConstraints(string $id, DomainNamePair $domainName, string $owner, Constraints $constraints): self
    {
        return new self(SpaceId::fromString($id), OwnerId::fromString($owner), $domainName, customConstraints: $constraints);
    }
}
