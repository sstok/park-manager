<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS\Violation;

use ParkManager\Application\Service\TLS\Violation;

final class UnsupportedDomain extends Violation
{
    private string $requiredPattern;
    private array $supported;

    public function __construct(string $requiredPattern, string ...$supported)
    {
        parent::__construct(\sprintf("The provided domain-names are not supported by required pattern. Required: '%s'\nProvided: '%s'.", $requiredPattern, \implode("', '", $supported)));

        $this->requiredPattern = $requiredPattern;
        $this->supported = $supported;
    }

    public function getTranslatorId(): string
    {
        return 'tls.violation.unsupported_domain';
    }

    public function getTranslationArgs(): array
    {
        return [
            'required_pattern' => $this->requiredPattern,
            'supported' => \implode(', ', $this->supported),
        ];
    }
}
