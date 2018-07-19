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

namespace ParkManager\Bundle\ServiceBusBundle\Tests\DependencyInjection\Compiler;

use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\MethodNameInflector\InvokeInflector;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use ParkManager\Bridge\PhpUnit\DefinitionArgumentEqualsServiceLocatorConstraint;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Compiler\MessageBusPass;
use ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Exception\CompilerPassException;
use ParkManager\Bundle\ServiceBusBundle\Tests\Fixtures\Handler\CancelUserHandler;
use ParkManager\Bundle\ServiceBusBundle\Tests\Fixtures\Handler\RegisterUserHandler;
use ParkManager\Bundle\ServiceBusBundle\Tests\Fixtures\Middleware\MessageGuardMiddleware;
use ParkManager\Bundle\ServiceBusBundle\Tests\Fixtures\Middleware\SecondaryMiddleware;
use ParkManager\Bundle\ServiceBusBundle\Tests\Fixtures\RegisterUser;
use ParkManager\Component\ServiceBus\TacticianCommandBus as CommandBus;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class MessageBusPassTest extends AbstractCompilerPassTestCase
{
    /** @test */
    public function it_registers_message_handlers()
    {
        $this->container->register(RegisterUserHandler::class)->addTag('park_manager.command_bus.users.handler');
        $this->registerService('park_manager.command_bus.users', CommandBus::class)->addTag('park_manager.service_bus');
        $this->registerService('park_manager.query_bus.users', CommandBus::class)->addTag('park_manager.service_bus');

        $this->compile();

        $this->expectMessageBus('park_manager.command_bus.users', [
            RegisterUser::class => RegisterUserHandler::class,
        ]);
        $this->expectMessageBus('park_manager.query_bus.users', []);
    }

    /** @test */
    public function it_registers_message_handlers_with_message_provided()
    {
        $this->container->register(RegisterUserHandler::class)
            ->addTag('park_manager.command_bus.users.handler', ['message' => 'RegisterCustomer']);

        $this->registerService('park_manager.command_bus.users', CommandBus::class)
            ->addTag('park_manager.service_bus');

        $this->compile();

        $this->expectMessageBus('park_manager.command_bus.users', [
            'RegisterCustomer' => RegisterUserHandler::class,
        ]);
    }

    /** @test */
    public function it_validates_auto_detection_of_supported_messages()
    {
        $this->container->register(RegisterUserHandler::class, \stdClass::class)
            ->addTag('park_manager.command_bus.users.handler');

        $this->registerService('park_manager.command_bus.users', CommandBus::class)
            ->addTag('park_manager.service_bus')->setArguments([null]);

        $this->expectException(CompilerPassException::class);
        $this->expectExceptionMessage(
            CompilerPassException::cannotDetectSupported(RegisterUserHandler::class)->getMessage()
        );

        $this->compile();
    }

    /** @test */
    public function it_validates_handler_class()
    {
        $this->container->register(RegisterUserHandler::class, 'Nope')
            ->addTag('park_manager.command_bus.users.handler');

        $this->registerService('park_manager.command_bus.users', CommandBus::class)
            ->addTag('park_manager.service_bus');

        $this->expectException(CompilerPassException::class);
        $this->expectExceptionMessage(
            CompilerPassException::unknownClass('Nope', RegisterUserHandler::class, 'park_manager.command_bus.users.handler')->getMessage()
        );

        $this->compile();
    }

    /** @test */
    public function it_validates_handler_class_duplication()
    {
        $this->container->register(RegisterUserHandler::class, 'Nope')
            ->addTag('park_manager.command_bus.users.handler');

        $this->registerService('park_manager.command_bus.users', CommandBus::class)
            ->addTag('park_manager.service_bus');

        $this->expectException(CompilerPassException::class);
        $this->expectExceptionMessage(
            CompilerPassException::unknownClass('Nope', RegisterUserHandler::class, 'park_manager.command_bus.users.handler')->getMessage()
        );

        $this->compile();
    }

    /** @test */
    public function it_validates_auto_detection_of_supported_messages_class_support()
    {
        $this->container->register(CancelUserHandler::class)
            ->addTag('park_manager.command_bus.users.handler');

        $this->registerService('park_manager.command_bus.users', CommandBus::class)
            ->addTag('park_manager.service_bus');

        $this->expectException(CompilerPassException::class);
        $this->expectExceptionMessage(
            CompilerPassException::cannotDetectSupported(CancelUserHandler::class)->getMessage()
        );

        $this->compile();
    }

    /** @test */
    public function it_registers_middlewares()
    {
        $this->container->register(RegisterUserHandler::class)
            ->addTag('park_manager.command_bus.users.handler');

        $this->registerService('park_manager.command_bus.users', CommandBus::class)
            ->addTag('park_manager.service_bus');

        $this->container->register('park_manager.command_bus.users.middleware.secondary', SecondaryMiddleware::class)->addTag('park_manager.command_bus.users.middleware');
        $this->container->register('park_manager.command_bus.users.middleware.guard', MessageGuardMiddleware::class)->addTag('park_manager.command_bus.users.middleware', ['priority' => 10]);

        $this->compile();

        $this->expectMessageBus(
            'park_manager.command_bus.users',
            [RegisterUser::class => RegisterUserHandler::class],
            ['park_manager.command_bus.users.middleware.guard', 'park_manager.command_bus.users.middleware.secondary']
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new MessageBusPass());
    }

    private function expectHandlerLocator(string $busId, array $handlerMap): void
    {
        self::assertThat(
            $this->container->findDefinition($busId.'.handler_locator'),
            new DefinitionArgumentEqualsServiceLocatorConstraint($this->container, 0, $handlerMap)
        );
    }

    private function expectMessageBus(string $busId, array $expectedHandlers, array $expectedMiddlewares = []): void
    {
        $expectedMiddlewares = array_map(function ($serviceId) {
            if (\is_string($serviceId)) {
                return new Reference($serviceId);
            }

            return $serviceId;
        }, $expectedMiddlewares);

        $this->expectHandlerLocator($busId, $expectedHandlers);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            $busId.'.__executor',
            0,
            array_merge($expectedMiddlewares, [$this->createHandlerLocatorMiddleware($busId)])
        );
    }

    private function createHandlerLocatorMiddleware($busId): Definition
    {
        $commandLocatorMiddleware = new Definition(CommandHandlerMiddleware::class);
        $commandLocatorMiddleware->setArguments([
            new Definition(ClassNameExtractor::class),
            new Reference($busId.'.handler_locator'),
            new Definition(InvokeInflector::class),
        ]);

        return $commandLocatorMiddleware;
    }
}
