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

namespace ParkManager\Module\Webhosting\Model\Package;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
interface Capability
{
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
     * @param array $from
     *
     * @return static
     */
    public static function reconstituteFromArray(array $from);
}
