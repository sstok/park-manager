<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class DomainNameSuffix extends Constraint
{
    public const INVALID_SYNTAX = '97e31849-cda5-4ec0-8a4f-8066e24a6aae';
    public const UNKNOWN_SUFFIX = 'c5bd436b-2bb4-4868-bbfb-3f4a4e6fcaf6';
    public const RESERVED_TLD_USED = 'a77c7fb3-0c3a-4859-bdc3-233371a1aebe';
    public const ICANN_UNKNOWN = '1d913ca2-029d-4b2b-be25-6ad22a70cee1';

    protected static $errorNames = [
        self::INVALID_SYNTAX => 'INVALID_SYNTAX',
        self::UNKNOWN_SUFFIX => 'UNKNOWN_SUFFIX',
        self::RESERVED_TLD_USED => 'RESERVED_TLD_USED',
        self::ICANN_UNKNOWN => 'ICANN_UNKNOWN',
    ];

    public function __construct(
        array $options = [],
        ?array $groups = null,
        mixed $payload = null,
        public bool $requireICANN = true,
    ) {
        parent::__construct($options, $groups, $payload);
    }
}
