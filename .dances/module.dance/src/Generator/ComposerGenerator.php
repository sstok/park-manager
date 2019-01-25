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

namespace ParkManager\SkeletonDancer\Generator;

use SkeletonDancer\Generator;
use SkeletonDancer\Service\Filesystem;
use SkeletonDancer\StringUtil;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use function json_encode;
use function str_replace;

final class ComposerGenerator implements Generator
{
    /** @var Filesystem */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    final public function generate(array $answers): void
    {
        $composer = [
            'name' => 'park-manager/' . $this->generatePackageSubName($answers),
            'type' => 'park-manager-module',
            'description' => 'Park-Manager ' . $answers['module_name'] . ' Module',
            'homepage' => 'https://www.park-manager.com/',
            'license' => 'MPL-2.0',
            'authors' => [
                [
                    'name' => $answers['author_name'],
                    'email' => $answers['author_email'],
                ],
                [
                    'name' => 'Community contributions',
                    'homepage' => 'https://github.com/park-manager/park-manager/contributors',
                ],
            ],
            'require' => ['php' => '^7.2', 'park-manager/core-module' => '^1.0'],
            'config' => [
                'preferred-install' => ['*' => 'dist'],
                'sort-packages' => true,
            ],
            'autoload' => [
                'psr-4' => [$answers['php_namespace'] . '\\' => ''],
                'exclude-from-classmap' => ['Tests/'],
            ],
            'autoload-dev' => [
                'psr-4' => [$answers['php_namespace'] . '\\Tests\\' => 'Tests'],
            ],
        ];

        $this->filesystem->dumpFile(
            'composer.json',
            // Add extra newline to content to fix content mismatch when dumping
            json_encode($composer, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES) . "\n"
        );
    }

    private function generatePackageSubName(array $answers): string
    {
        return str_replace('_', '-', StringUtil::underscore($answers['module_name'])) . '-module';
    }
}
