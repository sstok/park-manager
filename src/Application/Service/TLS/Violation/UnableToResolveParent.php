<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\TLS\Violation;

use ParkManager\Application\Service\TLS\Violation;

final class UnableToResolveParent extends Violation
{
    private string $name;

    public function __construct(string $name, int $code = 1)
    {
        parent::__construct((string) \openssl_error_string(), $code);
        $this->name = $name;
    }

    public function getTranslatorId(): string
    {
        return 'tls.violation.unable_to_resolve_parent';
    }

    public function getTranslationArgs(): array
    {
        return ['name' => $this->name];
    }
}
