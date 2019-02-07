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

final class ModuleGenerator implements Generator
{
    /** @var Twig_Environment */
    private $twig;

    /** @var Filesystem */
    private $filesystem;

    public function __construct(Twig_Environment $twig, Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->twig       = $twig;
    }

    public function generate(array $answers)
    {
        $this->filesystem->mkdir(
            [
                'Domain',

                'Application/Command',
                'Application/Query',
                'Application/Service/Finder',

                'Infrastructure/DependencyInjection',
                'Infrastructure/Resources/config/services',
                'Infrastructure/Resources/config/routing',
                'Infrastructure/Resources/templates',
                'Infrastructure/Resources/translations',

                'UserInterface/Web/Action',

                'Tests',
            ]
        );

        $this->filesystem->dumpFile('Infrastructure/DependencyInjection/DependencyExtension.php', $this->twig->render('extension.php.twig', $answers));
        $this->filesystem->dumpFile('Infrastructure/DependencyInjection/Configuration.php', $this->twig->render('configuration.php.twig', $answers));
        $this->filesystem->dumpFile('Infrastructure/Resources/config/services/core.php', $this->twig->render('services.php.twig', $answers));
        $this->filesystem->dumpFile('ParkManager' . $answers['module_name'] . 'Module.php', $this->twig->render('module.php.twig', $answers));
    }
}
