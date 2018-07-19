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

namespace ParkManager\Component\Module;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

abstract class AbstractParkManagerModule extends Bundle implements ParkManagerModule
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $extension = $this->createContainerExtension();

            if (null !== $extension) {
                if (!$extension instanceof ExtensionInterface) {
                    throw new \LogicException(
                        sprintf(
                            'Extension %s must implement Symfony\Component\DependencyInjection\Extension\ExtensionInterface.',
                            \get_class($extension)
                        )
                    );
                }

                // check naming convention
                // Park-Manager vendor Modules don't have to follow the alias convention
                $basename = preg_replace('/Module$/', '', $this->getName());
                $expectedAlias = Container::underscore($basename);

                if ($expectedAlias !== $extension->getAlias()) {
                    throw new \LogicException(
                        sprintf(
                            'Users will expect the alias of the default extension of a module to be the underscored version of the module name ("%s"). '.
                            'You can override "AbstractParkManagerModule::getContainerExtension()" if you want to use "%s" or another alias.',
                            $expectedAlias,
                            $extension->getAlias()
                        )
                    );
                }

                $this->extension = $extension;
            } else {
                $this->extension = false;
            }
        }

        if ($this->extension) {
            return $this->extension;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        if (0 !== \count($doctrineMapping = $this->getDoctrineMappings())) {
            $container->addCompilerPass(
                DoctrineOrmMappingsPass::createXmlMappingDriver($doctrineMapping, $this->getDoctrineEmNames())
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getContainerExtensionClass(): string
    {
        return $this->getNamespace().'\\Infrastructure\\DependencyInjection\\DependencyExtension';
    }

    protected function getDoctrineEmNames(): array
    {
        return [];
    }

    protected function getDoctrineMappings(): array
    {
        $path = $this->getPath().'/Infrastructure/Doctrine/';
        $namespace = $this->getNamespace();
        $mappings = [];

        if (file_exists($path)) {
            foreach (new \DirectoryIterator($path) as $node) {
                if ($node->isDot()) {
                    continue;
                }

                $basename = $node->getBasename();
                $directory = $path.$basename.'/Mapping';

                if (file_exists($directory)) {
                    $mappings[$directory] = $namespace.'\\Domain\\'.$basename;
                }
            }
        }

        return $mappings;
    }
}
