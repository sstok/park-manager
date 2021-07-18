<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Security;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

final class PermissionExpressionProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction(
                'permission',
                static fn ($arg): string => sprintf('new \ParkManager\Infrastructure\Security\PermissionExpression(%s)', implode(', ', \func_get_args())),
                static fn (array $variables, string $name, ...$arguments): PermissionExpression => new PermissionExpression($name, ...$arguments)
            ),
        ];
    }
}
