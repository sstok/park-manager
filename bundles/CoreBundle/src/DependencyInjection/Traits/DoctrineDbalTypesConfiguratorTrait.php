<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\DependencyInjection\Traits;

use Doctrine\DBAL\Types\Type as DbalType;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

trait DoctrineDbalTypesConfiguratorTrait
{
    /**
     * Registers the Doctrine DBAL Types (located in src/Doctrine).
     */
    public function registerDoctrineDbalTypes(ContainerBuilder $container, string $srcDirectory): void
    {
        $finder = new Finder();
        $finder->in($srcDirectory . '/Doctrine');
        $finder->name('*.php');
        $finder->files();

        $namespace = \preg_replace('/\\\DependencyInjection\\\DependencyExtension$/', '', static::class) . '\\Doctrine\\';
        $types = [];

        foreach ($finder as $node) {
            $className = $namespace . \str_replace('/', '\\', \mb_substr($node->getRelativePathname(), 0, -4));

            if (! \class_exists($className) || ! \is_subclass_of($className, DbalType::class)) {
                continue;
            }

            $r = new ReflectionClass($className);

            if ($r->isAbstract() || $r->isInterface() || $r->isTrait()) {
                continue;
            }

            $type = $r->newInstanceWithoutConstructor();
            \assert($type instanceof DbalType);
            $types[$type->getName()] = ['class' => $className];
        }

        $container->prependExtensionConfig('doctrine', [
            'dbal' => ['types' => $types],
        ]);
    }
}
