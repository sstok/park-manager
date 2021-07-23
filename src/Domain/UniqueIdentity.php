<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain;

use JsonSerializable;
use Stringable;

/**
 * Best used with {@see UuidTrait}.
 */
interface UniqueIdentity extends Stringable, JsonSerializable
{
    public static function fromString(string | Stringable $value): static;

    public function toString(): string;

    public function equals(?object $identity): bool;

    /**
     * Allows to easily compare the equality of an identity.
     *
     * NOTE: This will only return true if both identities
     * are of "this" instance type. Or both are null.
     */
    public static function equalsValue(?object $identity1, ?object $identity2): bool;

    /**
     * Allows to compare the public property (holding the actual identity) of an entity
     * against the given identity object.
     *
     * NOTE: This will only return true if both identities
     * are of "this" instance type. Or both are null.
     *
     * @param object|null $identity Identity (of this instance) object or null
     * @param object|null $entity   Entity object to extract the property from or null
     * @param string      $property The property-name of $entity to get the identity VO
     */
    public static function equalsValueOfEntity(?object $identity, ?object $entity, string $property): bool;
}
