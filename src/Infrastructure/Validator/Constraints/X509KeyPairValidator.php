<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Validator\Constraints;

use InvalidArgumentException;
use ParkManager\Application\Service\TLS\KeyValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class X509KeyPairValidator extends TLSCertificateValidator
{
    public function __construct(private KeyValidator $keyValidator)
    {
    }

    protected function checkConstraintType(Constraint $constraint): void
    {
        if (! $constraint instanceof X509KeyPair) {
            throw new UnexpectedTypeException($constraint, X509KeyPair::class);
        }
    }

    /**
     * @param X509KeyPair $constraint
     */
    protected function validateTLS(X509CertificateBundle $value, Constraint $constraint): void
    {
        if (! isset($value->privateKey)) {
            throw new InvalidArgumentException('No PrivateKey provided with X509CertificateBundle.');
        }

        $this->keyValidator->validate($value->privateKey, $value->certificate);
    }
}
