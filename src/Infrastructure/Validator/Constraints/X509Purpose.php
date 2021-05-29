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

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class X509Purpose extends Constraint
{
    public const PURPOSE_SMIME = CertificateValidator::PURPOSE_SMIME;
    public const PURPOSE_SMIME_ENCRYPTION = CertificateValidator::PURPOSE_SMIME_ENCRYPTION;
    public const PURPOSE_SMIME_SIGNING = CertificateValidator::PURPOSE_SMIME_SIGNING;

    public const PURPOSE_SSL_CLIENT = CertificateValidator::PURPOSE_SSL_CLIENT;
    public const PURPOSE_SSL_SERVER = CertificateValidator::PURPOSE_SSL_SERVER;

    /**
     * @var array<int, string>
     */
    public array $purposes = [];

    public function getDefaultOption(): string
    {
        return 'purposes';
    }

    public function getRequiredOptions(): array
    {
        return ['purposes'];
    }

    public function getTargets(): string | array
    {
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }
}
