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

namespace ParkManager\Bundle\ServiceBusPolicyGuardBundle\Guard;

use ParkManager\Component\ServiceBus\MessageGuard\PermissionGuard;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use function get_class;
use function is_bool;
use function is_int;
use function mb_strlen;
use function mb_strrpos;
use function mb_substr;
use function preg_match;

final class PolicyGuard implements PermissionGuard
{
    private $expressionLanguage;
    private $namespacePolicies;
    private $classPolicies;
    private $regexpPolicies;
    private $regexpPolicyMap;
    private $variables;

    public function __construct(ExpressionLanguage $expressionLanguage, array $namespacePolicies, array $classPolicies, string $regexpPolicies, array $regexpPolicyMap, array $variables)
    {
        $this->expressionLanguage = $expressionLanguage;
        $this->namespacePolicies  = $namespacePolicies;
        $this->classPolicies      = $classPolicies;
        $this->regexpPolicies     = $regexpPolicies;
        $this->regexpPolicyMap    = $regexpPolicyMap;
        $this->variables          = $variables;
    }

    public function decide(object $message): int
    {
        $policy = $this->getPolicyForMessageName(get_class($message));

        if (is_int($policy) || is_bool($policy)) {
            return (int) $policy;
        }

        $parameters            = $this->variables;
        $parameters['message'] = $message;
        $parameters['ALLOW']   = PermissionGuard::PERMISSION_ALLOW;
        $parameters['DENY']    = PermissionGuard::PERMISSION_DENY;
        $parameters['ABSTAIN'] = PermissionGuard::PERMISSION_ABSTAIN;

        /** @var Expression $policy */
        return (int) $this->expressionLanguage->evaluate($policy, $parameters);
    }

    /**
     * @return int|bool|string
     */
    private function getPolicyForMessageName(string $messageName)
    {
        if (isset($this->classPolicies[$messageName])) {
            return $this->classPolicies[$messageName];
        }

        $lastNsPos = mb_strrpos($messageName, '\\');
        $namespace = mb_substr($messageName, 0, $lastNsPos !== false ? $lastNsPos : mb_strlen($messageName));

        if (isset($this->namespacePolicies[$namespace])) {
            return $this->namespacePolicies[$namespace];
        }

        if (preg_match($this->regexpPolicies, $messageName, $matches) && isset($matches['MARK'])) {
            return $this->regexpPolicyMap[$matches['MARK']];
        }

        return self::PERMISSION_ABSTAIN;
    }
}
