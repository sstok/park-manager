<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS\Violation;

use DateTimeInterface;
use ParkManager\Application\Service\TLS\Violation;

final class CertificateIsExpired extends Violation
{
    private DateTimeInterface $expiredOn;

    public function __construct(DateTimeInterface $expiredOn)
    {
        parent::__construct(sprintf('The certificate has expired on "%s"', $expiredOn->format(\DATE_RFC3339)));

        $this->expiredOn = $expiredOn;
    }

    public function getTranslatorId(): string
    {
        return 'tls.violation.certificate_is_expired';
    }

    public function getTranslationArgs(): array
    {
        return [
            'expired_on' => $this->expiredOn,
        ];
    }
}
