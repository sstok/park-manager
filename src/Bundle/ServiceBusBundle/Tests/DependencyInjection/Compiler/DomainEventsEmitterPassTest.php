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

namespace ParkManager\Bundle\ServiceBusBundle\Tests\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Compiler\DomainEventsEmitterPass;
use ParkManager\Bundle\ServiceBusBundle\Tests\DependencyInjection\Fixture\EventListener\RegisterUserListener;
use ParkManager\Component\DomainEvent\Adapter\SymfonyEventEmitter;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
final class DomainEventsEmitterPassTest extends AbstractCompilerPassTestCase
{
    /** @test */
    public function it_registers_domain_listeners_for_message_buses()
    {
        $this->registerService('acme_something.foo.symfony', EventDispatcher::class);
        $this->registerService('acme_something.foo', SymfonyEventEmitter::class)
            ->addArgument(new Reference('acme_something.foo.symfony'))
            ->addTag('park_manager.service_bus.domain_event_emitter', ['bus-id' => 'park_manager.command_bus.user']);

        $this->registerService('acme_something.bar.symfony', EventDispatcher::class);
        $this->registerService('acme_something.bar', SymfonyEventEmitter::class)
            ->addArgument(new Reference('acme_something.bar.symfony'))
            ->addTag('park_manager.service_bus.domain_event_emitter', ['bus-id' => 'park_manager.command_bus.admin']);

        //--
        $this->registerService('park_manager.command_bus.user.bar', RegisterUserListener::class)
            ->addTag('park_manager.command_bus.user.domain_event_listener', ['event' => 'registerUser']);

        $this->compile();

        self::assertCount(0, $this->container->findDefinition('acme_something.foo')->getMethodCalls());
        self::assertCount(1, $this->container->findDefinition('acme_something.foo.symfony')->getMethodCalls());
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'acme_something.foo.symfony',
            'addListener',
            [
                'registerUser',
                [new ServiceClosureArgument(new Reference('park_manager.command_bus.user.bar')), 'onRegisterUser'],
                0, // priority - not relevant for this test
            ]
        );
    }

    /** @test */
    public function it_compiles_when_there_no_listeners_registered()
    {
        $this->registerService('acme_something.foo.symfony', EventDispatcher::class);
        $this->registerService('acme_something.foo', SymfonyEventEmitter::class)
            ->addArgument(new Reference('acme_something.foo.symfony'))
            ->addTag('park_manager.service_bus.domain_event_emitter', ['bus-id' => 'park_manager.command_bus.user']);

        $this->compile();

        self::assertCount(0, $this->container->findDefinition('acme_something.foo')->getMethodCalls());
        self::assertCount(0, $this->container->findDefinition('acme_something.foo.symfony')->getMethodCalls());
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DomainEventsEmitterPass(), PassConfig::TYPE_BEFORE_REMOVING, 1);
    }
}
