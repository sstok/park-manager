<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Validator\Constraints;

use Attribute;
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
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class X509Support extends Constraint
{
    /**
     * The callback receives the information as ([$rawDate], "$certificate", {CertificateValidator}).
     *
     * @var callable(array<string, mixed>, string, CertificateValidator): void
     */
    public $callback;

    /**
     * @param callable(array<string, mixed>, string, CertificateValidator): void $callback The callback or a set of options
     * @param mixed|null                                                         $payload
     */
    public function __construct(?callable $callback = null, ?array $groups = null, $payload = null, array $options = [])
    {
        // Invocation through annotations with an array parameter only
        if (\is_array($callback) && \count($callback) === 1 && isset($callback['value'])) {
            $callback = $callback['value'];
        }

        if (! \is_array($callback) || (! isset($callback['callback']) && ! isset($callback['groups']) && ! isset($callback['payload']))) {
            $options['callback'] = $callback;
        } else {
            $options = array_merge($callback, $options);
        }

        parent::__construct($options, $groups, $payload);
    }

    public function getDefaultOption(): string
    {
        return 'callback';
    }

    public function getRequiredOptions(): array
    {
        return ['callback'];
    }

    public function getTargets(): string | array
    {
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }
}
