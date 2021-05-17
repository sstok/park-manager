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
 * Execute the callback on the CertificateValidator::validateCertificateSupport().
 *
 * @see CertificateValidator::validateCertificateSupport()
 *
 * @Annotation
 * @Target({"ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class X509Support extends Constraint
{
    /**
     * The callback receives the information as ([$rawDate], "$certificate", {CertificateValidator}).
     *
     * @var callable
     */
    public $callback;

    /**
     * @param array|callable|null $options
     */
    public function __construct($options = null)
    {
        // Invocation through annotations with an array parameter only
        if (\is_array($options) && \count($options) === 1 && isset($options['value'])) {
            $options = $options['value'];
        }

        if (\is_array($options) && ! isset($options['callback']) && ! isset($options['groups']) && ! isset($options['payload'])) {
            $options = ['callback' => $options];
        }

        parent::__construct($options);
    }

    public function getDefaultOption(): string
    {
        return 'callback';
    }

    public function getRequiredOptions(): array
    {
        return ['callback'];
    }

    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }
}
