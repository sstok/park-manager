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

namespace ParkManager\Bundle\ServiceBusPolicyGuardBundle\Guard;

use ParkManager\Component\ServiceBus\MessageGuard\PermissionGuard;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class PolicyGuard implements PermissionGuard
{
    private $expressionLanguage;
    private $namespacePolicies;
    private $classPolicies;
    private $regexpPolicies;
    private $regexpPolicyMap;
    private $variables;

    public function __construct(
        ExpressionLanguage $expressionLanguage,
        array $namespacePolicies,
        array $classPolicies,
        string $regexpPolicies,
        array $regexpPolicyMap,
        array $variables
    ) {
        $this->expressionLanguage = $expressionLanguage;
        $this->namespacePolicies = $namespacePolicies;
        $this->classPolicies = $classPolicies;
        $this->regexpPolicies = $regexpPolicies;
        $this->regexpPolicyMap = $regexpPolicyMap;
        $this->variables = $variables;
    }

    public function decide(object $message): int
    {
        $messageName = \get_class($message);

        if (isset($this->classPolicies[$messageName])) {
            $policy = $this->classPolicies[$messageName];
        } elseif (isset($this->namespacePolicies[$namespace = mb_substr($messageName, 0, (false !== $p = mb_strrpos($messageName, '\\')) ? $p : mb_strlen($messageName))])) {
            $policy = $this->namespacePolicies[$namespace];
        } elseif (preg_match($this->regexpPolicies, $messageName, $matches) && isset($matches['MARK'])) {
            $policy = $this->regexpPolicyMap[$matches['MARK']];
        } else {
            return self::PERMISSION_ABSTAIN;
        }

        if (\is_bool($policy)) {
            return (int) $policy;
        }

        $parameters = $this->variables;
        $parameters['message'] = $message;
        $parameters['ALLOW'] = PermissionGuard::PERMISSION_ALLOW;
        $parameters['DENY'] = PermissionGuard::PERMISSION_DENY;
        $parameters['ABSTAIN'] = PermissionGuard::PERMISSION_ABSTAIN;

        /** @var Expression $policy */
        return (int) $this->expressionLanguage->evaluate($policy, $parameters);
    }
}
