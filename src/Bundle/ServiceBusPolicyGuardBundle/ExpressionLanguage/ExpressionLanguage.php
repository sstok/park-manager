<?php

declare(strict_types=1);

/*
 * This file is part of the Park-Manager project.
 *
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Bundle\ServiceBusPolicyGuardBundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;

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
