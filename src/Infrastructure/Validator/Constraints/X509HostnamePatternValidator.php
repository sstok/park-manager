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

final class X509HostnamePatternValidator extends TLSCertificateValidator
{
    public function __construct(private CertificateValidator $certificateValidator)
    {
    }

    protected function checkConstraintType(Constraint $constraint): void
    {
        if (! $constraint instanceof X509HostnamePattern) {
            throw new UnexpectedTypeException($constraint, X509HostnamePattern::class);
        }
    }

    /**
     * @param X509HostnamePattern $constraint
     */
    protected function validateTLS(X509CertificateBundle $value, Constraint $constraint): void
    {
        $this->certificateValidator->validateCertificateHost($value->certificate, $constraint->pattern);
    }
}
