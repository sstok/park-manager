<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Configuration;

use Attribute;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Permission extends Security
{
    /**
     * @param class-string       $name
     * @param array<int, string> $attrs all request-attribute names to be used (in order)
     */
    public function __construct(string $name, array $attrs = [], string $message = null, ?int $statusCode = null)
    {
        parent::__construct(
            [
                'expression' => sprintf(
                    'is_granted(permission("%s"%s))',
                    addslashes('\\' . trim($name, '\\')),
                    \count($attrs) > 0 ? ', ' . implode(', ', $attrs) : ''
                ),
            ],
            $message,
            $statusCode
        );
    }
}
