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

namespace ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\MessageBusConfigurator;
use ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\Middleware\PolicyGuardMiddlewareConfigurator;
use ParkManager\Bridge\ServiceBus\DependencyInjection\Configurator\MiddlewaresConfigurator;
use ParkManager\Bundle\ServiceBusPolicyGuardBundle\DependencyInjection\Compiler\PolicyGuardConfigurationPass;
use ParkManager\Bundle\ServiceBusPolicyGuardBundle\ExpressionLanguage\ExpressionLanguage;
use ParkManager\Bundle\ServiceBusPolicyGuardBundle\Guard\PolicyGuard;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ExpressionLanguageProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\DefaultsConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\ExpressionLanguage\Expression;

/**
 * @internal
 */
final class PolicyGuardConfigurationPassTest extends AbstractCompilerPassTestCase
{
    private const BUS_ID = 'park_manager.command_bus.users';

    /**
     * @var ServicesConfigurator
     */
    protected $containerConfigurator;

    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new PolicyGuardConfigurationPass());
    }

    protected function setUp()
    {
        parent::setUp();

        $instanceof = [];
        $this->containerConfigurator = new ServicesConfigurator(
            $this->container,
            new PhpFileLoader($this->container, $this->createMock(FileLocatorInterface::class)),
            $instanceof
        );
    }

    /** @test */
    public function it_processes_with_no_policies()
    {
        $di = $this->containerConfigurator->defaults();
        $this->createConfigurator($di);

        $this->compile();

        $guardId = self::BUS_ID.'.message_guard.'.PolicyGuard::class;
        $this->assertContainerBuilderHasService($guardId, PolicyGuard::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 0, new Reference(self::BUS_ID.'.policy_guard.expression_language'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 1, []);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 2, []);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 3, '{^/$}');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 4, []);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 5, ['services' => self::createServiceLocator([])]);
    }

    /** @test */
    public function it_processes_namespace_and_class_boolean_policies()
    {
        $di = $this->containerConfigurator->defaults();
        $configurator = $this->createConfigurator($di);
        $configurator->setNamespace('Fixtures', false);
        $configurator->setNamespace('Fixtures2', true);
        $configurator->setClass('Fixtures\MessageA', true);
        $configurator->setClass('Fixtures\MessageB', false);
        $configurator->setClass('Fixtures\MessageC', null);

        $this->compile();

        $guardId = self::BUS_ID.'.message_guard.'.PolicyGuard::class;
        $this->assertContainerBuilderHasService($guardId, PolicyGuard::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 0, new Reference(self::BUS_ID.'.policy_guard.expression_language'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 1, [
            'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures' => false,
            'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures2' => true,
        ]);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 2, [
            'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures\MessageA' => true,
            'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures\MessageB' => false,
        ]);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 3, '{^/$}');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 4, []);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 5, ['services' => self::createServiceLocator([])]);
    }

    /** @test */
    public function it_processes_namespace_and_class_expression_policies()
    {
        $di = $this->containerConfigurator->defaults();
        $configurator = $this->createConfigurator($di);
        $configurator->setVariable('foo', 'bar');
        $configurator->setVariable('repository', new Reference('ServiceA'));

        $configurator->setNamespace('Fixtures', 'DENY');
        $configurator->setNamespace('Fixtures2', 'repository.get(1) ? ALLOW : ABSTAIN');

        $this->compile();

        $guardId = self::BUS_ID.'.message_guard.'.PolicyGuard::class;
        $this->assertContainerBuilderHasService($guardId, PolicyGuard::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 0, new Reference(self::BUS_ID.'.policy_guard.expression_language'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 1, [
            'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures' => new Definition(Expression::class, ['DENY']),
            'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests\Fixtures2' => new Definition(Expression::class, ['repository.get(1) ? ALLOW : ABSTAIN']),
        ]);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 2, []);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 3, '{^/$}');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 4, []);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 5, [
            'foo' => 'bar',
            'services' => self::createServiceLocator(['repository' => 'ServiceA']),
        ]);
    }

    /** @test */
    public function it_processes_regexp_policies()
    {
        $di = $this->containerConfigurator->defaults();
        $configurator = $this->createConfigurator($di);
        $configurator->setRegexp('ParkManager\\\Bundle\\\ServiceBusPolicyGuardBundle\\\Tests\\\\Fixtures2\\\\Message[C]', 'ALLOW', false);
        $configurator->setRegexp('Message[A]', true);
        $configurator->setRegexp('Message[B]', false);
        $configurator->setRegexp('Message[2]', null);

        $this->compile();

        $guardId = self::BUS_ID.'.message_guard.'.PolicyGuard::class;
        $this->assertContainerBuilderHasService($guardId, PolicyGuard::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 0, new Reference(self::BUS_ID.'.policy_guard.expression_language'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 1, []);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 2, []);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 3, '{^(?|ParkManager\\\Bundle\\\ServiceBusPolicyGuardBundle\\\Tests\\\Fixtures2\\\Message[C](*:1))|ParkManager\\\Bundle\\\ServiceBusPolicyGuardBundle\\\Tests\\\(?|Message[A](*:2)|Message[B](*:3))$}su');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 4, [1 => new Definition(Expression::class, ['ALLOW']), 2 => true, 3 => false]);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument($guardId, 5, ['services' => self::createServiceLocator([])]);
    }

    /** @test */
    public function it_processes_ExpressionLanguageProviders()
    {
        $di = $this->containerConfigurator->defaults();
        $configurator = $this->createConfigurator($di);
        $configurator->addExpressionLanguageProvider(ExpressionLanguageProvider::class);
        $configurator->addExpressionLanguageProvider(ExpressionLanguageProvider::class.'2', ['foobar']);

        $this->compile();

        $this->assertContainerBuilderHasService(self::BUS_ID.'.policy_guard.expression_language', ExpressionLanguage::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(self::BUS_ID.'.policy_guard.expression_language', 0, new Reference('cache.system'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(self::BUS_ID.'.policy_guard.expression_language', 1, [
            new Reference(self::BUS_ID.'.policy_guard.expression_language.'.ExpressionLanguageProvider::class),
            new Reference(self::BUS_ID.'.policy_guard.expression_language.'.ExpressionLanguageProvider::class.'2'),
        ]);
    }

    private function createConfigurator(DefaultsConfigurator $di, ?string $namespacePrefix = 'ParkManager\Bundle\ServiceBusPolicyGuardBundle\Tests'): PolicyGuardMiddlewareConfigurator
    {
        $configurator = new PolicyGuardMiddlewareConfigurator(
            $midConfigurator = new MiddlewaresConfigurator(MessageBusConfigurator::extend($di, self::BUS_ID), $di, self::BUS_ID),
            $di,
            self::BUS_ID,
            $namespacePrefix,
            -10
        );

        self::assertSame($midConfigurator, $configurator->end());

        return $configurator;
    }

    private static function createServiceLocator(array $services): Definition
    {
        $services = array_map(
            function (string $service) {
                return new ServiceClosureArgument(new Reference($service));
            },
            $services
        );

        return (new Definition(ServiceLocator::class))->addTag('container.service_locator')
            ->addArgument($services)
            ->setPublic(false);
    }
}
