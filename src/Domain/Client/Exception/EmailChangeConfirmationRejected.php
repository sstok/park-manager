<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Client\Exception;

use InvalidArgumentException;

final class EmailChangeConfirmationRejected extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct(
            'Failed to accept e-mail address change confirmation. ' .
            'Token is invalid/expired or no request was registered.',
            1
        );
    }
}