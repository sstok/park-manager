<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\DataTransformer;

use Rollerworks\Component\SplitToken\SplitToken;
use Rollerworks\Component\SplitToken\SplitTokenFactory;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Throwable;

final class SplitTokenToStringTransformer implements DataTransformerInterface
{
    public function __construct(private SplitTokenFactory $splitTokenFactory)
    {
    }

    public function transform($value): string
    {
        // If a string was passed assume transformation in the Form failed
        if ($value === null || \is_string($value)) {
            return '';
        }

        if ($value instanceof SplitToken) {
            return $value->token()->getString();
        }

        throw new TransformationFailedException('Expected a SplitToken object.');
    }

    public function reverseTransform($value): ?SplitToken
    {
        if (! \is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        if ($value === '') {
            return null;
        }

        try {
            return $this->splitTokenFactory->fromString($value);
        } catch (Throwable $e) {
            throw new TransformationFailedException('Invalid SplitToken provided.', 0, $e);
        }
    }
}
