<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;

abstract class DomainIdType extends GuidType
{
    public const NAME = 'park_manager_domain_id';
    public const OBJECT_CLASS = null;

    final public function getName(): string
    {
        return static::NAME;
    }

    final public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    final public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null || \is_string($value)) {
            return $value;
        }

        return $value->toString();
    }

    final public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        $class = static::OBJECT_CLASS;

        return $class::fromString($value);
    }
}
