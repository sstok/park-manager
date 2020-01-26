<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain;

use ParkManager\Domain\Client\ClientId;

/**
 * OwnerId links an entity to either a Client user, the Internal system or Private Reseller.
 *
 * _This should only be used when the entity is not Client specific._
 *
 * The static IDs are used for the following:
 *
 * - Internal is managed by the system itself and used for platform configuration.
 *   Mainly the VirtualHost configuration of the hosting-management application
 *   is marked as `internal`, and reseller entry points.
 *
 * - Private marks the Entity is only accessible by Administrators, this used
 *   for corporate email mailboxes and company owned websites.
 *
 * A `personal` id owner contains the ClientId.
 */
final class OwnerId
{
    use UuidTrait;

    public const INTERNAL = '9667ac52-9038-11e8-b175-4a0003ae49a0';
    public const PRIVATE = 'ae0efe1e-9038-11e8-9ebe-4a0003ae49a0';
    public const PERSONAL = 'personal';

    public static function internal(): self
    {
        return self::fromString(self::INTERNAL);
    }

    public static function private(): self
    {
        return self::fromString(self::PRIVATE);
    }

    public static function fromUserId(ClientId $id): self
    {
        return self::fromString($id->toString());
    }

    /**
     * @param string $id Either one of the class constant's (INTERNAL, PRIVATE, PERSONAL) value
     */
    public function is(string $id): bool
    {
        if ($this->stringValue === $id) {
            return true;
        }

        return $id === self::PERSONAL && $this->stringValue !== self::INTERNAL && $this->stringValue !== self::PRIVATE;
    }

    public function isOwnedBy(ClientId $id): bool
    {
        return $id->toString() === $this->stringValue;
    }
}
