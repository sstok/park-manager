<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\ServiceBusPolicyGuardBundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class ExpressionLanguage extends BaseExpressionLanguage
{
    protected function registerFunctions()
    {
        parent::registerFunctions();

        $this->register('id_equals', function (string $id1, string $id2): string {
            return sprintf('((string) %1$s === (string) %2$s))', $id1, $id2);
        }, function (array $variables, $id1, $id2): bool {
            return (string) $id1 === (string) $id2;
        });

        $this->register('id_same', function (string $id1, string $id2): string {
            return sprintf('((!(%1$s instanceof %2$s)) ? false : %1$s->equals(%2$s))', $id1, $id2);
        }, function (array $variables, object $id1, object $id2): bool {
            return (!($id1 instanceof $id2)) ? false : $id1->equals($id2);
        });

        $this->register('has_method', function (string $object, string $method): string {
            return sprintf('method_exists(%s, %s)', $object, $method);
        }, function (array $variables, object $object, string $method): bool {
            return method_exists($object, $method);
        });
    }
}
