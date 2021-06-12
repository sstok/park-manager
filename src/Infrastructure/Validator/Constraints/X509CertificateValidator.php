<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Validator\Constraints;

use ParkManager\Application\Service\TLS\CertificateValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class X509CertificateValidator extends TLSCertificateValidator
{
    public function __construct(private CertificateValidator $certificateValidator)
    {
    }

    protected function checkConstraintType(Constraint $constraint): void
    {
        if (! $constraint instanceof X509Certificate) {
            throw new UnexpectedTypeException($constraint, X509Certificate::class);
        }
    }

    protected function validateTLS(X509CertificateBundle $value, Constraint $constraint): void
    {
        $this->certificateValidator->validateCertificate($value->certificate, $value->caList);
    }
}
