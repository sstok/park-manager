<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Type\Webhosting;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use ParkManager\Domain\Webhosting\ScheduledTask\CronCondition;
use ParkManager\Domain\Webhosting\ScheduledTask\MomentCondition;
use Stringable;

final class ScheduledTaskConditionType extends Type
{
    public function getName(): string
    {
        return 'park_manager_scheduled_task_condition';
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return $platform->getVarcharTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        \assert($value instanceof Stringable || \is_string($value));

        return (string) $value;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        if (str_starts_with($value, '@') || mb_substr_count($value, ' ') > 1) {
            return new CronCondition($value);
        }

        return new MomentCondition($value);
    }
}
