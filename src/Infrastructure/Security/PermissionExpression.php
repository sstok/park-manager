<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Security;

/**
 * Internal holder class for permissions used in the ExpressionLanguage and Twig.
 *
 * @internal
 */
final class PermissionExpression implements Permission
{
    /** @var array<int, mixed> */
    public array $arguments;

    public function __construct(public string $name, mixed ...$arguments)
    {
        $this->arguments = $arguments;
    }
}
