<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\DataTransformer;

use ParkManager\Domain\ByteSize;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

final class ByteSizeToArrayTransformer implements DataTransformerInterface
{
    /**
     * @return array{isInf: bool, value: float|int, unit: string}|null
     */
    public function transform(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof ByteSize) {
            throw new UnexpectedTypeException($value, ByteSize::class);
        }

        if ($value->isInf()) {
            return [
                'isInf' => true,
                'value' => 0,
                'unit' => 'b',
            ];
        }

        [$value, $unit] = explode(' ', $value->format());

        return [
            'isInf' => false,
            'value' => (float) $value,
            'unit' => mb_strtolower($unit),
        ];
    }

    public function reverseTransform(mixed $value): ?ByteSize
    {
        if (! \is_array($value)) {
            throw new UnexpectedTypeException($value, 'array');
        }

        if ($value['isInf'] ?? false) {
            return ByteSize::inf();
        }

        if (! isset($value['value'], $value['unit'])) {
            return null;
        }

        if ($value['unit'] === 'byte' && preg_match('/\.[1-9]/', (string) $value['value']) === 1) {
            throw new TransformationFailedException(
                'Fractions are not accepted for Byte unit.',
                invalidMessage: 'Fractions are not accepted for Byte unit.'
            );
        }

        return new ByteSize((float) $value['value'], $value['unit']);
    }
}
