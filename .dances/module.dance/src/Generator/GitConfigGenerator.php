<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\SkeletonDancer\Generator;

use SkeletonDancer\Generator;
use SkeletonDancer\Service\Filesystem;
use function implode;

final class GitConfigGenerator implements Generator
{
    /** @var Filesystem */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function generate(array $configuration)
    {
        $this->filesystem->dumpFile('.gitignore', "/vendor/\nphpunit.xml\n");
        $this->filesystem->dumpFile(
            '.gitattributes',
            implode(
                "\n",
                [
                    "# Always use LF\ncore.autocrlf=lf",
                    '',
                    '.gitattributes export-ignore',
                    '.gitignore export-ignore',
                    'phpunit.xml.dist export-ignore',
                    '/Tests export-ignore',
                ]
            ) . "\n"
        );
    }
}
