<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Domain\Package;

/**
 * A Capabilities indicate what a webhosting package supports.
 *
 * A Capability may cover a single subject like support for MySQL
 * (indicating a maximum number of databases), or a combined subject
 * like mail (max mailboxes, max forwards, max aliases).
 *
 * It works like a Constraint, holds a specific configuration
 * and unware of the package it's used in.
 *
 * A Capability is immutable and sharable.
 */
interface Capability
{
    /**
     * Returns a unique id (UUID) for storage mapping.
     *
     * This must never change! Moving/renaming the
     * Capability class should not require updating
     * the databse.
     */
    public static function id(): string;

    /**
     * Return the current configuration of this Capability.
     *
     * The configuration must be in a format that can be stored
     * in JSON and be reconstitute using reconstituteFromArray().
     *
     * @return array
     */
    public function configuration(): array;

    /**
     * Reconstitute this Capability from an array.
     *
     * Note: the provided values come directly from
     * storage and are not transformed yet.
     *
     * @param array $from
     *
     * @return static
     */
    public static function reconstituteFromArray(array $from);
}
