<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\Finder\Finder;
use Throwable;

final class AliasResolver
{
    /**
     * Converts a class (like an entity) to a shorter alias.
     *
     * Lowercase with underscores, namespace parts separated by dots.
     *
     * With namespace prefix 'ParkManager\Domain\':
     *   * 'ParkManager\Domain\Webhosting\Space\Space' is converted to 'webhosting.space'
     *   * 'ParkManager\Domain\Webhosting\Ftp\FtpUser' is converted to 'webhosting.ftp.ftp_user'
     *   * 'ParkManager\Domain\User' is converted to 'user'
     */
    public static function getClassAlias(string $className, string $namespacePrefix): string
    {
        $className = ltrim($className, '\\');
        $namespacePrefix = rtrim($namespacePrefix, '\\') . '\\';

        if (! str_starts_with($className, $namespacePrefix)) {
            throw new InvalidArgumentException(sprintf('Expected class %s to begin with "%s"', $className, $namespacePrefix));
        }

        $class = mb_substr($className, mb_strlen($namespacePrefix));
        $parts = explode('\\', $class);

        // Normalize 'Space\Space' to 'Space'.
        if (\count($parts) > 1 && $parts[\count($parts) - 1] === $parts[\count($parts) - 2]) {
            array_pop($parts);
        }

        $parts = array_map([self::class, 'underscore'], $parts);

        return implode('.', $parts);
    }

    private static function underscore(string $string): string
    {
        return mb_strtolower(preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], $string));
    }

    /**
     * @return array<int, class-string>
     */
    public static function findFiles(Finder $finder, string $namespace, ?callable $typeFilter = null): array
    {
        $finder
            ->ignoreDotFiles(true)
            ->files()
        ;

        $typeFilter ??= 'class_exists';
        $namespace = trim($namespace, '\\') . '\\';
        $typeNames = [];

        foreach ($finder as $file) {
            $typeName = $file->getFilenameWithoutExtension();
            $subPath = $file->getRelativePath();

            if ($subPath !== '') {
                $typeName = str_replace('/', '\\', $subPath) . '\\' . $typeName;
            }

            $fqCn = $namespace . $typeName;

            if (self::isTypeAccepted($fqCn, $typeFilter)) {
                $typeNames[] = $fqCn;
            }
        }

        return $typeNames;
    }

    private static function isTypeAccepted(string $fqCn, callable $typeFilter): bool
    {
        try {
            return $typeFilter($fqCn);
        } catch (Throwable) {
            return false;
        }
    }
}
