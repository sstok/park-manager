<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

final class LocalizedLabelCollectionTransformer implements DataTransformerInterface
{
    public function transform($value): ?array
    {
        if ($value === null) {
            return null;
        }

        unset($value['_default']);
        $entries = [];

        foreach ($value as $locale => $label) {
            $entries[$locale] = ['locale' => $locale, 'value' => $label];
        }

        return $entries;
    }

    public function reverseTransform($value): array
    {
        $values = [];

        foreach ($value as $entry) {
            $values[$entry['locale']] = $entry['value'];
        }

        return $values;
    }
}
