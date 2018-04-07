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

namespace ParkManager\Component\Module\Traits;

use Doctrine\DBAL\Types\Type as DbalType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

/**
 * Helps with automatically registering Doctrine DBAL types.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
trait DoctrineDbalTypesConfiguratorTrait
{
    /**
     * Registers the Doctrine DBAL Types (located in Module/Infrastructure/Doctrine).
     *
     * Overwrite this method to skip/change registering.
     * All types are assumed to be commented.
     *
     * @param ContainerBuilder $container
     * @param string           $moduleDirectory
     */
    protected function registerDoctrineDbalTypes(ContainerBuilder $container, string $moduleDirectory): void
    {
        if (!file_exists($moduleDirectory.'/Infrastructure/Doctrine')) {
            return;
        }

        $finder = new Finder();
        $finder->in($moduleDirectory.'/Infrastructure/Doctrine');
        $finder->name('*.php');
        $finder->files();

        $namespace = preg_replace('/\\\DependencyInjection\\\DependencyExtension$/', '', static::class).'\\Doctrine\\';
        $types = [];

        foreach ($finder as $node) {
            $className = $namespace.str_replace('/', '\\', mb_substr($node->getRelativePathname(), 0, -4));

            if (class_exists($className) && is_subclass_of($className, DbalType::class)) {
                /** @var DbalType $type */
                $type = (new \ReflectionClass($className))->newInstanceWithoutConstructor();
                $types[$type->getName()] = ['class' => $className, 'commented' => true];
            }
        }

        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => $types,
            ],
        ]);
    }
}
