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
    public string $certificate;

    /**
     * Private key provided as memory-protected string.
     */
    public ?HiddenString $privateKey;

    /**
     * @var array<string,string> [user-provided CA-name => X509 contents]
     */
    public array $caList;

    /**
     * @param array<string,string> $caList [user-provided CA-name => X509 contents]
     */
    public function __construct(string $certificate, ?HiddenString $privateKey = null, array $caList = [])
    {
        $this->certificate = $certificate;
        $this->privateKey = $privateKey;
        $this->caList = $caList;
    }
}
