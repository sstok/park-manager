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

use ParkManager\SkeletonDancer\Configurator\HttpActionConfigurator;
use Rollerworks\Tools\SkeletonDancer\Configuration\Config;
use Rollerworks\Tools\SkeletonDancer\Generator;
use Rollerworks\Tools\SkeletonDancer\Service\Filesystem;
use Rollerworks\Tools\SkeletonDancer\StringUtil;

final class HttpActionGenerator implements Generator
{
    private $filesystem;
    private $config;
    private $twig;

    public function __construct(Filesystem $filesystem, Config $config, \Twig_Environment $twig)
    {
        $this->filesystem = $filesystem;
        $this->config = $config;
        $this->twig = $twig;
    }

    public function generate(array $configuration)
    {
        $this->assertActionsDirectory();

        $template = 'http/action';

        if ('general' !== $configuration['action_type']) {
            $template .= '_'.$configuration['action_type'];
        }

        $template .= '.php.twig';

        $this->filesystem->dumpFile(
            '@currentDir/'.$configuration['action_name'].$configuration['module_section'].'Action.php',
            $this->twig->render(
                $template,
                [
                    'action_name' => $configuration['action_name'],
                    'namespace' => $configuration['namespace'],
                    'module_alias' => StringUtil::underscore($configuration['module_name']),
                    'module_name' => $configuration['module_name'],
                    'module_section' => $configuration['module_section'],
                ]
            )
        );

        // XXX Automatic registering of the action in the route definition :)
    }

    public function getConfigurators()
    {
        return [HttpActionConfigurator::class];
    }

    private function assertActionsDirectory()
    {
        if ('Action' !== $this->config->get('current_dir_name')) {
            throw new \InvalidArgumentException(
                'The current directory does not seem to belong to HTTP Actions.'
            );
        }
    }
}
