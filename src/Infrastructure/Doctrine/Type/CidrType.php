<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use IPLib\Address\AddressInterface;
use IPLib\Factory as IPFactory;
use IPLib\Range\RangeInterface;

/**
 * The cidr type works only with PostgreSQL!
 */
final class CidrType extends Type
{
    public const NAME = 'cidr';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return 'cidr';
    }

    /**
     * @throws ConversionException
     *
     * @return RangeInterface|AddressInterface|null
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ($value === '') {
            return null;
        }

        // CIDR always returns with the netmask. But we don't always use a network range.
        if ((str_contains($value, ':') && str_ends_with($value, '/128'))
            || (! str_contains($value, ':') && str_ends_with($value, '/32'))) {
            $result = IPFactory::parseAddressString((string) preg_replace('{/\d+$}', '', $value));
        } else {
            $result = IPFactory::parseRangeString($value);
        }

        if ($result === null) {
            throw ConversionException::conversionFailed($value, self::NAME);
        }

        return $result;
    }

    /**
     * @throws ConversionException
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof RangeInterface) {
            return $value->asSubnet()->toString();
        }

        if ($value instanceof AddressInterface) {
            return $value->toString();
        }

        throw ConversionException::conversionFailedInvalidType($value, self::NAME, [RangeInterface::class, AddressInterface::class, 'null']);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
