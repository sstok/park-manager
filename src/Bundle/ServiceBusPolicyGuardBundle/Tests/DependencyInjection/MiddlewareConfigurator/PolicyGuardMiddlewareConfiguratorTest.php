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

namespace ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\DependencyInjection\MiddlewareConfigurator;

use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\MessageBusConfigurator;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\Middleware\PolicyGuardMiddlewareConfigurator;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator\MiddlewaresConfigurator;
use ParkManager\Bundle\ServiceBusBundle\Test\DependencyInjection\MiddlewareConfiguratorTestCase;
use ParkManager\Bundle\ServiceBusPolicyGuardBundle\ExpressionLanguage\ExpressionLanguage;
use ParkManager\Bundle\ServiceBusPolicyGuardBundle\Guard\PolicyGuard;
use Symfony\Component\DependencyInjection\ExpressionLanguageProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class PolicyGuardMiddlewareConfiguratorTest extends MiddlewareConfiguratorTestCase
{
    private const BUS_ID = 'park_manager.command_bus.users';

    /** @test */
    public function its_registered()
    {
        $di = $this->containerConfigurator->defaults();
        $this->createConfigurator($di);

        $this->assertContainerBuilderHasService(self::BUS_ID.'.policy_guard.expression_language', ExpressionLanguage::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(self::BUS_ID.'.policy_guard.expression_language', 0, new Reference('cache.system'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(self::BUS_ID.'.policy_guard.expression_language', 1, []);

        $guardId = self::BUS_ID.'.message_guard.'.PolicyGuard::class;
        $this->assertContainerBuilderHasService($guardId, PolicyGuard::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag($guardId, self::BUS_ID.'.message_guard', ['priority' => -10]);
        $this->assertContainerBuilderHasServiceDefinitionWithTag($guardId, 'park_manager.service_bus.policy_guard', ['bus-id' => self::BUS_ID]);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 0, new Reference(self::BUS_ID.'.policy_guard.expression_language'));
    }

    /** @test */
    public function its_skips_registration_when_priority_is_null()
    {
        $di = $this->containerConfigurator->defaults();
        $configurator = $this->createConfigurator($di, null, null);
        $configurator->setVariable('foo', 'bar');

        $this->assertContainerBuilderNotHasService(self::BUS_ID.'.policy_guard.expression_language');
    }

    /** @test */
    public function it_registers_ExpressionLanguageProvider()
    {
        $di = $this->containerConfigurator->defaults();
        $configurator = $this->createConfigurator($di);

        $configurator->addExpressionLanguageProvider(ExpressionLanguageProvider::class);
        $configurator->addExpressionLanguageProvider(ExpressionLanguageProvider::class.'2', ['foobar']);

        $serviceId = self::BUS_ID.'.policy_guard.expression_language.'.ExpressionLanguageProvider::class;
        $this->assertContainerBuilderHasService($serviceId, ExpressionLanguageProvider::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag($serviceId, self::BUS_ID.'.policy_guard.expression_language_provider');

        $serviceId = self::BUS_ID.'.policy_guard.expression_language.'.ExpressionLanguageProvider::class.'2';
        $this->assertContainerBuilderHasService($serviceId, ExpressionLanguageProvider::class.'2');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, 0, 'foobar');
        $this->assertContainerBuilderHasServiceDefinitionWithTag($serviceId, self::BUS_ID.'.policy_guard.expression_language_provider');
    }

    /** @test */
    public function it_registers_variables()
    {
        $di = $this->containerConfigurator->defaults();
        $configurator = $this->createConfigurator($di);

        $configurator->setVariable('foo', 'bar');
        $configurator->setVariable('car', 'fool');

        $serviceId = self::BUS_ID.'.policy_guard.variable.foo';
        $this->assertContainerBuilderHasService($serviceId);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, 0, 'foo');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, 1, 'bar');
        $this->assertContainerBuilderHasServiceDefinitionWithTag($serviceId, self::BUS_ID.'.policy_guard.variable');

        $serviceId = self::BUS_ID.'.policy_guard.variable.car';
        $this->assertContainerBuilderHasService($serviceId);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, 0, 'car');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, 1, 'fool');
        $this->assertContainerBuilderHasServiceDefinitionWithTag($serviceId, self::BUS_ID.'.policy_guard.variable');
    }

    /** @test */
    public function it_registers_namespace_policies()
    {
        $di = $this->containerConfigurator->defaults();
        $configurator = $this->createConfigurator($di, 'ParkManager\Bundle\ServiceBusPolicyGuardBundle');

        $configurator->setNamespace('Tests\Fixtures', true);
        $configurator->setNamespace('ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures2', 'ALLOW', false);

        $serviceId = self::BUS_ID.'.policy_guard.ns_policy.ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures';
        $this->assertContainerBuilderHasService($serviceId);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, 0, 'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, 1, true);
        $this->assertContainerBuilderHasServiceDefinitionWithTag($serviceId, self::BUS_ID.'.policy_guard.ns_policy');

        $serviceId = self::BUS_ID.'.policy_guard.ns_policy.ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures2';
        $this->assertContainerBuilderHasService($serviceId);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, 0, 'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures2');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, 1, 'ALLOW');
        $this->assertContainerBuilderHasServiceDefinitionWithTag($serviceId, self::BUS_ID.'.policy_guard.ns_policy');
    }

    /** @test */
    public function it_registers_class_policies()
    {
        $di = $this->containerConfigurator->defaults();
        $configurator = $this->createConfigurator($di, 'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures');

        $configurator->setClass('MessageA', true);
        $configurator->setClass('ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures\MessageB', 'ALLOW', false);

        $serviceId = self::BUS_ID.'.policy_guard.class_policy.ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures\MessageA';
        $this->assertContainerBuilderHasService($serviceId);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, 0, 'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures\MessageA');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, 1, true);
        $this->assertContainerBuilderHasServiceDefinitionWithTag($serviceId, self::BUS_ID.'.policy_guard.class_policy');

        $serviceId = self::BUS_ID.'.policy_guard.class_policy.ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures\MessageB';
        $this->assertContainerBuilderHasService($serviceId);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, 0, 'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures\MessageB');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, 1, 'ALLOW');
        $this->assertContainerBuilderHasServiceDefinitionWithTag($serviceId, self::BUS_ID.'.policy_guard.class_policy');
    }

    /** @test */
    public function it_registers_regexp_policies()
    {
        $di = $this->containerConfigurator->defaults();
        $configurator = $this->createConfigurator($di, 'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures');

        $configurator->setRegexp('Message[A]', true);
        $configurator->setRegexp('ParkManager\\\Bundle\\\ServiceBusPolicyGuardBundle\\\Tests\\\\Fixtures\\\\Message[B]', 'ALLOW', false);

        $serviceId = self::BUS_ID.'.policy_guard.regexp_policy.'.sha1('ParkManager\\Bundle\\ServiceBusPolicyGuardBundle\\Tests\\Fixtures\\Message[A]');
        $this->assertContainerBuilderHasService($serviceId);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, 0, 'Message[A]');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, 1, true);
        $this->assertContainerBuilderHasServiceDefinitionWithTag($serviceId, self::BUS_ID.'.policy_guard.regexp_policy', ['prefix' => 'ParkManager\\Bundle\\ServiceBusPolicyGuardBundle\\Tests\\Fixtures\\']);

        $serviceId = self::BUS_ID.'.policy_guard.regexp_policy.'.sha1('ParkManager\\\Bundle\\\ServiceBusPolicyGuardBundle\\\Tests\\\\Fixtures\\\\Message[B]');
        $this->assertContainerBuilderHasService($serviceId);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, 0, 'ParkManager\\\Bundle\\\ServiceBusPolicyGuardBundle\\\Tests\\\\Fixtures\\\\Message[B]');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($serviceId, 1, 'ALLOW');
        $this->assertContainerBuilderHasServiceDefinitionWithTag($serviceId, self::BUS_ID.'.policy_guard.regexp_policy', ['prefix' => '']);
    }

    /** @test */
    public function it_validates_policy_value()
    {
        $di = $this->containerConfigurator->defaults();
        $configurator = $this->createConfigurator($di);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Policy for "MessageA" must be: boolean, null or a string.');

        $configurator->setClass('MessageA', -1);
    }

    /** @test */
    public function it_validates_regexp()
    {
        $di = $this->containerConfigurator->defaults();
        $configurator = $this->createConfigurator($di, 'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Regex policy "{^Message[A$}su" cannot be compiled, error: Compilation failed: missing terminating ] for character class at offset 11');

        $configurator->setRegexp('Message[A', true);
    }

    private function createConfigurator(DefaultsConfigurator $di, ?string $namespacePrefix = null, ?int $priority = -10): PolicyGuardMiddlewareConfigurator
    {
        $configurator = new PolicyGuardMiddlewareConfigurator(
            $midConfigurator = new MiddlewaresConfigurator(MessageBusConfigurator::extend($di, self::BUS_ID), $di, self::BUS_ID),
            $di,
            self::BUS_ID,
            $namespacePrefix,
            $priority
        );

        self::assertSame($midConfigurator, $configurator->end());

        return $configurator;
    }
}
