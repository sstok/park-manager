<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain;

trait EnumEqualityTrait
{
    public static function equalsTo(?self $instance, ?self $other): bool
    {
        if ($instance === null || $other === null) {
            return false;
        }

        return $instance === $other;
    }

    public static function equalsToAny(?self $instance, ?self ...$other): bool
    {
        if ($instance === null) {
            return false;
        }

        foreach ($other as $value) {
            if ($value !== null && $instance === $value) {
                return true;
            }
        }

        return false;
    }
}
