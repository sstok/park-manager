<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS;

use ParagonIE\HiddenString\HiddenString;
use ParkManager\Domain\DomainName\TLS\Certificate;

interface CertificateFactory
{
    /**
     * @param string               $contents The raw Certificate contents X509 encoded
     * @param array<string,string> $caList   List of CA contents (with their name)
     */
    public function createCertificate(string $contents, HiddenString $privateKey, array $caList = []): Certificate;
}
