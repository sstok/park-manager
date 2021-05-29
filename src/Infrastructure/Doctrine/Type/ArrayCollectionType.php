<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;

final class ArrayCollectionType extends JsonType
{
    /**
     * @param Collection<string, mixed>|null $value
     *
     * @throws \JsonException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode($value->toArray(), \JSON_THROW_ON_ERROR, 512);
    }

    /**
     * @throws \JsonException
     *
     * @return ArrayCollection<string, mixed>
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ArrayCollection
    {
        if ($value === null || $value === '') {
            return new ArrayCollection();
        }

        $value = \is_resource($value) ? stream_get_contents($value) : $value;

        return new ArrayCollection(json_decode($value, true, 512, \JSON_THROW_ON_ERROR));
    }

    public function getName(): string
    {
        return 'array_collection';
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
