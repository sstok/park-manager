<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS\Violation;

use Error;
use ParkManager\Application\Service\TLS\Violation;

final class UnprocessableKey extends Violation
{
    public function __construct(string $message = '')
    {
        parent::__construct($message, 0, new Error(openssl_error_string() ?: 'Unknown error', 1));
    }

    public function getTranslatorId(): string
    {
        return 'tls.violation.unprocessable_key';
    }

    public function getTranslationArgs(): array
    {
        return [];
    }
}
