<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

abstract class EnumType extends Type
{
    public const NAME = 'park_manager_enum_type';
    public const OBJECT_CLASS = null;
    public const ENUM_TYPE = 'string';

    final public function getName(): string
    {
        return static::NAME;
    }

    final public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if (static::ENUM_TYPE === 'string') {
            return $platform->getVarcharTypeDeclarationSQL($column);
        }

        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    final public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        return $value->value;
    }

    final public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        $class = static::OBJECT_CLASS;

        return $class::from($value);
    }
}
