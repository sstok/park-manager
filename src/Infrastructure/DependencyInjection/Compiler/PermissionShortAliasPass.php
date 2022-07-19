<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\DependencyInjection\Compiler;

use ParkManager\Infrastructure\Security\Permission;
use ParkManager\Infrastructure\Security\PermissionAccessManager;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

final class PermissionShortAliasPass implements CompilerPassInterface
{
    private const NAMESPACE_PREFIX = 'ParkManager\\Infrastructure\\Security\\Permission\\';

    public function __construct(
        private string $directory,
        private string $namespacePrefix = self::NAMESPACE_PREFIX
    ) {
    }

    public function process(ContainerBuilder $container): void
    {
        $finder = new Finder();
        $finder
            ->in($this->directory)
            ->name('{(?<!Decider)\.php$}')
        ;

        $typeFilter = static fn (string $class): bool => class_exists($class)
                                                     && is_a($class, Permission::class, true)
                                                     && ! (new ReflectionClass($class))->isAbstract();

        $classes = AliasResolver::findFiles($finder, $this->namespacePrefix, $typeFilter);
        $permissionShortNames = [];

        foreach ($classes as $class) {
            $permissionShortNames[AliasResolver::getClassAlias($class, $this->namespacePrefix)] = $class;
        }

        ksort($permissionShortNames);

        $def = $container->findDefinition(PermissionAccessManager::class);
        $def->setArgument(2, $permissionShortNames);
    }
}
