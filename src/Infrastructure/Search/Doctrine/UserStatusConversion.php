<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Search\Doctrine;

use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Orm\ColumnConversion;
use Rollerworks\Component\Search\Doctrine\Orm\ValueConversion;

final class UserStatusConversion implements ColumnConversion, ValueConversion
{
    public function convertValue($value, array $options, ConversionHints $hints): string
    {
        return '1';
    }

    public function convertColumn(string $column, array $options, ConversionHints $hints): string
    {
        $value = $hints->originalValue;

        return match ($value) {
            'active' => \sprintf('(CASE WHEN %s.passwordExpiresOn IS NULL OR %1$s.passwordExpiresOn > %s THEN 1 ELSE 0 END)', $hints->field->alias, $hints->createParamReferenceFor(new \DateTimeImmutable(), 'datetime_immutable')),
            'password-expired' => \sprintf('(CASE WHEN %s.passwordExpiresOn IS NOT NULL AND %1$s.passwordExpiresOn < %s THEN 1 ELSE 0 END)', $hints->field->alias, $hints->createParamReferenceFor(new \DateTimeImmutable(), 'datetime_immutable')),
            'email-change-pending' => \sprintf('(CASE WHEN %s.emailAddressChangeToken.selector IS NOT NULL THEN 1 ELSE 0 END)', $hints->field->alias),
        };
    }
}
