<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS\Violation;

use ParkManager\Application\Service\TLS\Violation;

final class GlobalWildcard extends Violation
{
    private string $provided;
    private string $suffixPattern;

    public function __construct(string $provided, string $suffixPattern)
    {
        $this->provided = $provided;
        $this->suffixPattern = $suffixPattern;
    }

    public function getTranslatorId(): string
    {
        if ($this->suffixPattern === '*') {
            return 'tls.violation.global_wildcard';
        }

        return 'tls.violation.public_suffix_wildcard';
    }

    public function getTranslationArgs(): array
    {
        return [
            'provided' => $this->provided,
            'suffix_pattern' => $this->suffixPattern,
        ];
    }
}
