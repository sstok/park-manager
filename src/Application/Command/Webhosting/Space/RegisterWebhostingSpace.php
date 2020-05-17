<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Space;

use ParkManager\Domain\User\UserId;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\ConstraintSetId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class RegisterWebhostingSpace
{
    /**
     * READ-ONLY.
     *
     * @var SpaceId
     */
    public $id;

    /**
     * READ-ONLY.
     *
     * @var \ParkManager\Domain\DomainName\DomainNamePair
     */
    public $domainName;

    /**
     * READ-ONLY.
     *
     * @var UserId|null
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

    private function __construct(string $id, ?string $owner, DomainNamePair $domainName, ?string $setId, ?Constraints $constraints)
    {
        $this->id = SpaceId::fromString($id);
        $this->domainName = $domainName;
        $this->customConstraints = $constraints;

        if ($owner !== null) {
            $this->owner = UserId::fromString($owner);
        }

        if ($setId !== null) {
            $this->constraintSetId = ConstraintSetId::fromString($setId);
        }
    }

    public static function withConstraintSet(string $id, DomainNamePair $domainName, ?string $owner, string $setId): self
    {
        return new self($id, $owner, $domainName, $setId, null);
    }

    public static function withCustomConstraints(string $id, DomainNamePair $domainName, ?string $owner, Constraints $constraints): self
    {
        return new self($id, $owner, $domainName, null, $constraints);
    }
}
