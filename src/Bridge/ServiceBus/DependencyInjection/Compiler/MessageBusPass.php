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

namespace ParkManager\Bridge\ServiceBus\DependencyInjection\Compiler;

use League\Tactician\CommandBus;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\MethodNameInflector\InvokeInflector;
use ParkManager\Bridge\ServiceBus\DependencyInjection\ContainerLocator;
use ParkManager\Bridge\ServiceBus\DependencyInjection\Exception\CompilerPassException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * The MessageBusPass registers the message-bus handlers
 * and there Middlewares.
 *
 * **Note:** Because this CompilerPass set-up all message-bus configurations
 * it's not possible to register other message-buses and middlewares after
 * this pass is executed.
 */
final class MessageBusPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * @var ContainerBuilder|null
     */
    private $container;

    public function process(ContainerBuilder $container): void
    {
        $this->container = $container;

        foreach ($container->findTaggedServiceIds('park_manager.service_bus', true) as $busId => $tags) {
            self::assertSingleTag($tags, $busId, 'park_manager.service_bus');

            // Use a private embeddable service to ease testing code.
            $container->register($busId.'.handler_locator', ContainerLocator::class)->setPrivate(true)
                ->addArgument(ServiceLocatorTagPass::register($container, $this->findMessageHandlers($busId.'.handler')));

            $commandHandlerDef = new Definition(CommandHandlerMiddleware::class);
            $commandHandlerDef->setArguments([
                new Definition(ClassNameExtractor::class),
                new Reference($busId.'.handler_locator'),
                new Definition(InvokeInflector::class),
            ]);

            $middlewares = $this->findAndSortTaggedServices($busId.'.middleware', $container);
            $middlewares[] = $commandHandlerDef;

            $container->register($busId.'.__executor', CommandBus::class)->setPrivate(true)->addArgument($middlewares);
            $container->findDefinition($busId)->setArgument(0, new Reference($busId.'.__executor'));
        }

        $this->container = null;
    }

    private static function assertSingleTag(array $tags, string $busId, string $tagName): void
    {
        if (\count($tags) > 1) {
            throw CompilerPassException::toManyTags($busId, $tagName);
        }
    }

    private function findMessageHandlers(string $tagName): array
    {
        $handlers = [];

        foreach ($this->container->findTaggedServiceIds($tagName, true) as $serviceId => $tags) {
            self::assertSingleTag($tags, $serviceId, $tagName);

            $handlerClass = $this->assertClassExists($serviceId, $tagName);
            $message = $tags[0]['message'] ?? $this->findHandlingClass($handlerClass, $serviceId);

            if (isset($handlers[$message])) {
                throw CompilerPassException::duplicateMessageHandler($message, $serviceId, (string) $handlers[$message]);
            }

            $handlers[$message] = new Reference($serviceId);
        }

        return $handlers;
    }

    private function findHandlingClass(string $handlerClass, string $serviceId): string
    {
        $handlerReflection = $this->container->getReflectionClass($handlerClass);

        if (!$handlerReflection->hasMethod('__invoke')) {
            throw CompilerPassException::cannotDetectSupported($serviceId);
        }

        $method = $handlerReflection->getMethod('__invoke');

        if (!$method->isPublic() || $method->isAbstract() ||
            $method->getNumberOfRequiredParameters() !== 1 || null === $class = $method->getParameters()[0]->getClass()
        ) {
            throw CompilerPassException::cannotDetectSupported($serviceId);
        }

        return $class->name;
    }

    private function assertClassExists(string $serviceId, string $tagName): string
    {
        /** @var string $className */
        $className = $this->container->getParameterBag()->resolveValue($this->container->findDefinition($serviceId)->getClass());

        if (!class_exists($className)) {
            throw CompilerPassException::unknownClass($className, $serviceId, $tagName);
        }

        return $className;
    }
}
