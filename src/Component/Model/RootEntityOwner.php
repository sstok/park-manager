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

namespace ParkManager\Component\Model;

/**
 * The RootEntityOwner reference is used to link an owner (eg. user or system entity)
 * to a root Entity (Account, Domain registration, Mailbox etc).
 *
 * Each Owner (or identity) has a unique id (uuid).
 * Which (for uuid's) may be equal to the id of the identity.
 *
 * When the actual identity uses a different technique for id's,
 * the owner-id should be mapped to the external identity's id.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class RootEntityOwner
{
    public const SYSTEM_ID = '58a7d010-10d4-11e8-8ec4-4a0003ae49a0';

    use UuidTrait;

    /**
     * Indicates the Entity is owned by the system (not a single identity).
     */
    public static function system(): self
    {
        return self::fromString(self::SYSTEM_ID);
    }

    public function isSystem(self $other): bool
    {
        return $other->equals($this);
    }
}
