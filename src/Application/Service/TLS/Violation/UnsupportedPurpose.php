<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS\Violation;

use ParkManager\Application\Service\TLS\Violation;
use ParkManager\Domain\TranslatableMessage;

final class UnsupportedPurpose extends Violation
{
    private string $requiredPurpose;

    public function __construct(string $requiredPurpose)
    {
        parent::__construct(sprintf('Certificate does not support purpose: %s.', $requiredPurpose));

        $this->requiredPurpose = $requiredPurpose;
    }

    public function getTranslatorId(): string
    {
        return 'tls.violation.unsupported_purpose';
    }

    public function getParameters(): array
    {
        return [
            'required_purpose' => new TranslatableMessage($this->requiredPurpose, domain: 'messages'),
        ];
    }
}
