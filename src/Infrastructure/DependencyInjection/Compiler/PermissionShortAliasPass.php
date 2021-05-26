<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\DependencyInjection\Compiler;

use ParkManager\Infrastructure\Security\PermissionAccessManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

final class PermissionShortAliasPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $permissionShortNames = [];

        $finder = new Finder();
        $finder
            ->in(\dirname(__DIR__, 2) . '/Security/Permission')
            ->files()
            ->name('{(?<!Decider)\.php$}')
        ;

        foreach ($finder as $file) {
            $className = $file->getFilenameWithoutExtension();
            $subPath = $file->getRelativePath();
            $name = self::underscore($className);

            if ($subPath !== '') {
                $name = self::underscore(str_replace(['/', '\\'], '.', $subPath)) . '.' . $name;
                $className = str_replace('/', '\\', $subPath) . '\\' . $className;
            }

            $permissionShortNames[$name] = 'ParkManager\\Infrastructure\\Security\\Permission\\' . $className;
        }

        $def = $container->findDefinition(PermissionAccessManager::class);
        $def->setArgument(2, $permissionShortNames);
    }

    private static function underscore(string $string): string
    {
        return mb_strtolower(preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], $string));
    }
}
