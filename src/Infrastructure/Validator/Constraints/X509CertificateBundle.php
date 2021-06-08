<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Validator\Constraints;

use ParagonIE\HiddenString\HiddenString;

/**
 * DTO to provide a X.509 Certificate bundle with (optional) private-key
 * and CA-list to validators.
 */
final class X509CertificateBundle
{
    /**
     * @param HiddenString|null     $privateKey private key provided as memory-protected string
     * @param array<string, string> $caList     [user-provided CA-name => X509 contents]
     */
    public function __construct(
        public string $certificate,
        public ?HiddenString $privateKey = null,
        public array $caList = []
    ) {
    }
}
