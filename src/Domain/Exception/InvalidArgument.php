<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Exception;

use InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatableInterface;

class InvalidArgument extends InvalidArgumentException implements DomainError
{
    public function getTranslatorMsg(): string | TranslatableInterface
    {
        return 'Invalid Argument provided.';
    }

    public function getPublicMessage(): string
    {
        return 'Invalid Argument provided.';
    }
}
