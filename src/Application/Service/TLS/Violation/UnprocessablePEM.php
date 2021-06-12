<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS\Violation;

use Error;
use InvalidArgumentException;
use ParkManager\Application\Service\TLS\Violation;

/**
 * This exception class is used for when the data cannot be processed or parse.
 */
final class UnprocessablePEM extends Violation
{
    public function __construct(private string $certName, string $contents = '')
    {
        $previous = $contents !== '' ? new InvalidArgumentException($contents) : null;
        parent::__construct('', 0, new Error((string) openssl_error_string(), 1, $previous));
    }

    public function getTranslatorId(): string
    {
        return 'tls.violation.unprocessable_pem';
    }

    public function getParameters(): array
    {
        return ['name' => $this->certName];
    }
}
