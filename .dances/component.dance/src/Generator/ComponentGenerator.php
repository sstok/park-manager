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

final class ComponentGenerator implements Generator
{
    private $twig;
    private $filesystem;

    public function __construct(\Twig_Environment $twig, Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->twig       = $twig;
    }

    public function generate(array $answers)
    {
        $this->filesystem->mkdir(
            ['Tests']
        );
    }
}
