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
use Twig_Environment;

final class ReadMeGenerator implements Generator
{
    /** @var Filesystem */
    private $filesystem;

    /** @var Twig_Environment */
    private $twig;

    public function __construct(Twig_Environment $twig, Filesystem $filesystem)
    {
        $this->twig       = $twig;
        $this->filesystem = $filesystem;
    }

    public function generate(array $answers)
    {
        $this->filesystem->dumpFile('README.md', $this->twig->render('readme.md.twig', $answers));
    }
}
