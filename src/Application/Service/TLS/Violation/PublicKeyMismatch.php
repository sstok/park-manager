<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS\Violation;

use ParkManager\Application\Service\TLS\Violation;

final class PublicKeyMismatch extends Violation
{
    public function __construct()
    {
        parent::__construct('The public-key of the certificate does not match with the provided private-key.');
    }

    public function getTranslatorId(): string
    {
        return 'tls.violation.public_key_mismatch';
    }
}
