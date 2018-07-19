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

namespace ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\Middleware;

use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\AdvancedMiddlewareConfigurator;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\MiddlewaresConfigurator;
use ParkManager\Bundle\ServiceBusPolicyGuardBundle\DependencyInjection\PolicyConfigHolder;
use ParkManager\Bundle\ServiceBusPolicyGuardBundle\ExpressionLanguage\ExpressionLanguage;
use ParkManager\Bundle\ServiceBusPolicyGuardBundle\Guard\PolicyGuard;
use Symfony\Component\DependencyInjection\Loader\Configurator\AbstractServiceConfigurator;
use Symfony\Component\DependencyInjection\Reference;

final class PolicyGuardMiddlewareConfigurator implements AdvancedMiddlewareConfigurator
{
    private $parent;
    private $serviceId;
    private $namespacePrefix;
    private $di;

    public function __construct(
        MiddlewaresConfigurator $parent,
        AbstractServiceConfigurator $di,
        string $serviceId,
        ?string $namespacePrefix = '',
        ?int $priority = -10
    ) {
        if (null !== $namespacePrefix) {
            $namespacePrefix = trim($namespacePrefix, '\\').'\\';
        }

        $this->parent = $parent;
        $this->serviceId = $serviceId;
        $this->namespacePrefix = $namespacePrefix ?? '';
        $this->di = $di;

        // Skip registration. To allow extending without loosing the current priority.
        if (null === $priority) {
            return;
        }

        $this->di->set($this->serviceId.'.policy_guard.expression_language', ExpressionLanguage::class)
            ->args([new Reference('cache.system'), []])->private();

        $this->di->set($this->serviceId.'.message_guard.'.PolicyGuard::class, PolicyGuard::class)->private()
            ->tag($this->serviceId.'.message_guard', ['priority' => $priority])
            ->tag('park_manager.service_bus.policy_guard', ['bus-id' => $this->serviceId])
            ->args([new Reference($this->serviceId.'.policy_guard.expression_language')]);
    }

    public function addExpressionLanguageProvider(string $class, array $arguments = []): self
    {
        $this->di->set($this->serviceId.'.policy_guard.expression_language.'.$class, $class)->private()
            ->tag($this->serviceId.'.policy_guard.expression_language_provider')
            ->args($arguments);

        return $this;
    }

    /**
     * @param string                                $name
     * @param string|int|float|bool|array|Reference $value
     *
     * @return self
     */
    public function setVariable(string $name, $value): self
    {
        if (\in_array($name, $r = ['message', 'services', 'token'], true)) {
            throw new \InvalidArgumentException(sprintf('Variables "%s" are reserved and cannot be overwritten.', implode('", "', $r)));
        }

        if (!preg_match('/^[a-z][a-z0-9_]*$/si', $name)) {
            throw new \InvalidArgumentException(sprintf('Variable name %s has an invalid syntax.', json_encode($name)));
        }

        $this->di->set($this->serviceId.'.policy_guard.variable.'.$name, PolicyConfigHolder::class)
            ->tag($this->serviceId.'.policy_guard.variable')->args([$name, $value])
            ->private()->autowire(false);

        return $this;
    }

    /**
     * @param string           $namespace
     * @param string|bool|null $policy
     *
     * @return PolicyGuardMiddlewareConfigurator
     */
    public function setNamespace(string $namespace, $policy, bool $usePrefix = true): self
    {
        self::assertPolicy($namespace, $policy);

        if ($usePrefix) {
            $namespace = $this->namespacePrefix.$namespace;
        }

        $this->di->set($this->serviceId.'.policy_guard.ns_policy.'.$namespace, PolicyConfigHolder::class)
            ->tag($this->serviceId.'.policy_guard.ns_policy')->args([$namespace, $policy])
            ->private()->autowire(false);

        return $this;
    }

    /**
     * @param string           $className
     * @param string|bool|null $policy
     *
     * @return self
     */
    public function setClass(string $className, $policy, bool $usePrefix = true): self
    {
        self::assertPolicy($className, $policy);

        if ($usePrefix) {
            $className = $this->namespacePrefix.$className;
        }

        $this->di->set($this->serviceId.'.policy_guard.class_policy.'.$className, PolicyConfigHolder::class)
            ->tag($this->serviceId.'.policy_guard.class_policy')->args([$className, $policy])
            ->private()->autowire(false);

        return $this;
    }

    /**
     * Register a Regexp based policy.
     *
     * Caution: The regexp is combined into a whole regex, all matches be enclosed in groups.
     * And contain no delimiters, flags and/or `^` and '$'.
     *
     * @param string           $regexp
     * @param string|bool|null $policy
     * @param bool             $usePrefix
     *
     * @return self
     */
    public function setRegexp(string $regexp, $policy, bool $usePrefix = true): self
    {
        self::assertPolicy($regexp, $policy);
        self::assertRegexp($regexp);

        $serviceId = sha1(($usePrefix ? $this->namespacePrefix : '').$regexp);
        $service = $this->di->set($this->serviceId.'.policy_guard.regexp_policy.'.$serviceId, PolicyConfigHolder::class)
            ->args([$regexp, $policy])->private()->autowire(false);

        if ($usePrefix) {
            $service->tag($this->serviceId.'.policy_guard.regexp_policy', ['prefix' => $this->namespacePrefix]);
        } else {
            $service->tag($this->serviceId.'.policy_guard.regexp_policy', ['prefix' => '']);
        }

        return $this;
    }

    public function end(): MiddlewaresConfigurator
    {
        return $this->parent;
    }

    private static function assertPolicy(string $string, $policy): void
    {
        if (!\is_bool($policy) && null !== $policy && !\is_string($policy)) {
            throw new \InvalidArgumentException(
                sprintf('Policy for "%s" must be: boolean, null or a string.', $string)
            );
        }
    }

    private static function assertRegexp(string $regexp): void
    {
        set_error_handler(function ($type, $message) { throw new \InvalidArgumentException($message); }
        );
        try {
            preg_match($regexp = '{^'.$regexp.'$}su', '');
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Regex policy "%s" cannot be compiled, error: %s',
                    $regexp,
                    mb_substr($e->getMessage(), 14)
                )
            );
        } finally {
            restore_error_handler();
        }
    }
}
