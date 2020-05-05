<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS\Violation;

use ParkManager\Application\Service\TLS\Violation;

final class KeyBitsToLow extends Violation
{
    private int $expected;
    private int $provided;

    public function __construct(int $expected, int $provided)
    {
        $this->expected = $expected;
        $this->provided = $provided;
    }

    public function getTranslatorId(): string
    {
        return 'tls.violation.key_bits_to_low';
    }

    public function getTranslationArgs(): array
    {
        return [
            'expected' => $this->expected,
            'provided' => $this->provided,
        ];
    }
}
