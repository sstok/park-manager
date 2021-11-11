<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Sequentially;

/**
 * @Annotation
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class ValidUsername extends Compound
{
    /**
     * @param array<string, mixed> $options
     */
    protected function getConstraints(array $options): array
    {
        return [
            new Sequentially(
                [
                    new NotBlank(allowNull: false, normalizer: 'trim'),
                    new Length(min: 2, max: 20),
                    new Regex('{^\p{L}([_-]?[\p{L}\d]+)+$}u', 'invalid_username'),
                ]
            ),
        ];
    }
}
