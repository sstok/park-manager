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

abstract class BaseComposerGenerator implements Generator
{
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    final public function generate(array $answers)
    {
        $composer = [
            'name' => 'park-manager/'.$this->generatePackageSubName($answers),
            'type' => $this->getType(),
            'description' => $this->getDescription($answers),
            'homepage' => 'https://www.park-manager.com/',
            'license' => $this->getLicense(),
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
            'require' => ['php' => '^7.2'] + $this->getRequires(),
            'config' => [
                'preferred-install' => [
                    '*' => 'dist',
                ],
                'sort-packages' => true,
            ],
            'autoload' => [
                'psr-4' => [
                    $answers['php_namespace'].'\\' => '',
                ],
                'exclude-from-classmap' => [
                    'Tests/',
                ],
            ],
            'autoload-dev' => [
                'psr-4' => [
                    $answers['php_namespace'].'\\Tests\\' => 'Tests',
                ],
            ],
        ];

        $this->filesystem->dumpFile(
            'composer.json',
            // Add extra newline to content to fix content mismatch when dumping
            json_encode($composer, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES)."\n"
        );
    }

    abstract protected function generatePackageSubName(array $answers): string;

    abstract protected function getType(): string;

    abstract protected function getDescription(array $answers): string;

    abstract protected function getRequires(): array;

    protected function getLicense(): string
    {
        return 'MPL-2.0';
    }
}
