<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Space;

use ParkManager\Domain\OwnerId;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\ConstraintSetId;
use ParkManager\Domain\Webhosting\DomainName;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceId;

final class RegisterWebhostingSpace
{
    /**
     * READ-ONLY.
     *
     * @var WebhostingSpaceId
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
     * @var ConstraintSetId|null
     */
    public $constraintSetId;

    /**
     * READ-ONLY.
     *
     * @var Constraints|null
     */
    public $customConstraints;

    private function __construct(string $id, string $owner, DomainName $domainName, ?string $setId, ?Constraints $constraints)
    {
        $this->id = WebhostingSpaceId::fromString($id);
        $this->domainName = $domainName;
        $this->customConstraints = $constraints;
        $this->owner = OwnerId::fromString($owner);

        if ($setId !== null) {
            $this->constraintSet = ConstraintSetId::fromString($setId);
        }
    }

    public static function withConstraintSet(string $id, DomainName $domainName, string $owner, string $setId): self
    {
        return new self($id, $owner, $domainName, $setId, null);
    }

    public static function withCustomConstraints(string $id, DomainName $domainName, string $owner, Constraints $constraints): self
    {
        return new self($id, $owner, $domainName, null, $constraints);
    }
}
