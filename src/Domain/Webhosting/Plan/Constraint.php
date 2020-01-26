<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Plan;

/**
 * A Constraints set indicate what a webhosting plan supports.
 *
 * A Constraint may cover a single subject like support for MySQL
 * (indicating a maximum number of databases), or a combined subject
 * like mail (max mailboxes, max forwards, max aliases).
 *
 * It works like a Constraint, holds a specific configuration
 * and is unaware of the plan it's used in.
 *
 * A Constraint is immutable and sharable.
 */
interface Constraint
{
    /**
     * Return the current configuration of this Constraint.
     *
     * The configuration must be in a format that can be stored
     * in JSON and be reconstitute using reconstituteFromArray().
     */
    public function configuration(): array;

    /**
     * Reconstitute this Constraint from an array.
     *
     * Note: the provided values come directly from
     * storage and are not transformed yet.
     *
     * @return static
     */
    public static function reconstituteFromArray(array $from);
}
