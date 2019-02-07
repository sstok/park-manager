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

final class ChangelogGenerator implements Generator
{
    /** @var Filesystem */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function generate(array $configuration)
    {
        $this->filesystem->dumpFile(
            'CHANGELOG.md',
            <<<'BODY'
Change Log
==========

All notable changes to this publication will be documented in this file.

## 1.0.0 - ????-??-??

First stable release.

BODY
        );
    }
}
