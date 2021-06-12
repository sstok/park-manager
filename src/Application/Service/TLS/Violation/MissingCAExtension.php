<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS\Violation;

use ParkManager\Application\Service\TLS\Violation;

final class MissingCAExtension extends Violation
{
    public function __construct(private string $name)
    {
        parent::__construct('Certificate does not contain required "CA:true" in "extensions.basicExtension".');
    }

    public function getTranslatorId(): string
    {
        return 'tls.violation.certificate_is_ca';
    }

    public function getParameters(): array
    {
        return ['common_name' => $this->name];
    }
}
