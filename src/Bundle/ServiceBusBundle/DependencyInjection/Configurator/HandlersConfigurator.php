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

namespace ParkManager\Bundle\ServiceBusBundle\DependencyInjection\Configurator;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\AbstractServiceConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * @internal
 */
final class HandlersConfigurator
{
    private $parent;
    private $serviceId;
    private $di;
    private $fileLocator;

    public function __construct(MessageBusConfigurator $parent, AbstractServiceConfigurator $di, string $serviceId, string $currentDirectory)
    {
        $this->parent = $parent;
        $this->serviceId = $serviceId;
        $this->di = $di;
        $this->fileLocator = new FileLocator($currentDirectory);
    }

    /**
     * Register a single handler service.
     *
     * @param string $handlerClass
     * @param array  $arguments
     *
     * @return $this
     */
    public function register(string $handlerClass, array $arguments = []): self
    {
        $this->di->set($this->serviceId.'.handler.'.$handlerClass, $handlerClass)
            ->tag($this->serviceId.'.handler')
            ->args($arguments)
            ->private();

        return $this;
    }

    /**
     * Register a single handler service.
     *
     * @param string $handlerClass
     * @param string $messageName  (automatically detected if omitted)
     *
     * @return $this
     */
    public function registerFor(string $handlerClass, string $messageName, array $arguments = []): self
    {
        $this->di->set($this->serviceId.'.handler.'.$handlerClass, $handlerClass)
            ->tag($this->serviceId.'.handler', ['message' => $messageName])
            ->args($arguments)
            ->private();

        return $this;
    }

    /**
     * Overwrite an existing handler service.
     *
     * @param string $currentHandlerClass
     * @param string $newHandlerClass
     * @param int    $priority            Service decoration-priority
     * @param array  $arguments
     *
     * @return $this
     */
    public function overwrite(string $currentHandlerClass, string $newHandlerClass, int $priority = 0, array $arguments = []): self
    {
        $this->di->set($this->serviceId.'.handler.'.$newHandlerClass, $newHandlerClass)
            ->decorate($currentHandlerClass, null, $priority)->private()
            ->args($arguments);

        return $this;
    }

    /**
     * Load a set of handler services using PSR-4 for discovery.
     *
     * The resource location is relative to the location specified
     * in MessageBusConfigurator::handlers().
     *
     * @param string      $namespace The namespace prefix of classes in the scanned directory
     * @param string      $resource  The directory to look for classes, glob-patterns allowed
     * @param string|null $exclude   A globed path of files to exclude
     *
     * @return HandlersConfigurator
     */
    public function load(string $namespace, string $resource, string $exclude = null): self
    {
        // First register handlers the classes into a temporary Container builder.
        (new PhpFileLoader($containerBuilder = new ContainerBuilder(), $this->fileLocator))
            ->registerClasses((new Definition())->addTag($this->serviceId.'.handler'), $namespace, $resource, $exclude);

        // And then register them in the actual ContainerBuilder
        // using the defaults of ServicesConfigurator.
        foreach ($containerBuilder->getDefinitions() as $id => $definition) {
            if (!$definition->hasTag($this->serviceId.'.handler')) {
                continue;
            }

            // Id holds the class-name. But we need to prefix to prevent collisions.
            $this->di->set($this->serviceId.'.handler.'.$id, $id)->tag($this->serviceId.'.handler')->private();
        }

        return $this;
    }

    public function end(): MessageBusConfigurator
    {
        return $this->parent;
    }
}
