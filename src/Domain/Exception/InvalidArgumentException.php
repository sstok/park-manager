<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Exception;

use InvalidArgumentException as BaseInvalidArgumentException;

final class InvalidArgumentException extends BaseInvalidArgumentException implements TranslatableException
{
    public function getTranslatorId(): string
    {
        return 'Invalid Argument provided.';
    }

    public function getTranslationArgs(): array
    {
        return [];
    }
}
