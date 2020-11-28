<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class DirectoryPath extends Constraint
{
    public const INVALID_PATH = '50ebe2e5-b1c1-4e20-80d2-67003e2ded08';
    public const PATH_TO_LONG_ERROR = 'ed8913ef-0801-473a-af36-8801218d5d98';
    public const NAME_TO_LONG_ERROR = 'c2450dab-e979-4290-a954-d35a3e5dc2cd';

    protected static $errorNames = [
        self::INVALID_PATH => 'INVALID_PATH',
        self::PATH_TO_LONG_ERROR => 'PATH_TO_LONG_ERROR',
        self::NAME_TO_LONG_ERROR => 'NAME_TO_LONG_ERROR',
    ];

    public string $message = 'This value should be a valid directory path.';
    public string $pathToLongMessage = 'The path should not be more than {{ maximum }} characters long.';
    public string $nameToLongMessage = 'The name within in a path should not be more than {{ maximum }} bytes.';
}
