<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Space;

use ParkManager\Domain\EnumTrait;
use ParkManager\Domain\Webhosting\Space\Exception\InvalidStatus;

final class SpaceSetupStatus
{
    use EnumTrait;

    public const ERROR = -1;
    public const REINITIALIZED = 0;
    public const REGISTERED = 1;
    public const GETTING_INITIALIZED = 2;
    public const READY = 3;

    public static function validateNewStatus(self $current, self $newStatus): void
    {
        if ($current->value === self::READY) {
            throw new InvalidStatus('Cannot change status when already initialized.');
        }

        if ($newStatus->value !== self::ERROR) {
            if ($newStatus->value < $current->value) {
                throw new InvalidStatus('Cannot change status to a lower value unless new status is Error.');
            }

            // Special case that is considered valid.
            if ($current->value === self::REINITIALIZED && $newStatus->value === self::GETTING_INITIALIZED) {
                return;
            }

            if ($newStatus->value > ($current->value + 1)) {
                throw new InvalidStatus('Cannot increase status with more than one greater value.');
            }
        }
    }

    public function label(): string
    {
        return self::getLabel($this);
    }

    public static function getLabel(self $value): string
    {
        return match ($value->value) {
            self::ERROR => 'error',
            self::REINITIALIZED => 'reinitialized',
            self::REGISTERED => 'registered',
            self::GETTING_INITIALIZED => 'getting_initialized',
            self::READY => 'initialized',
        };
    }
}
