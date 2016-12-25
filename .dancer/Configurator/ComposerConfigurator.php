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

namespace ParkManager\SkeletonDancer\Configurator;

use Rollerworks\Tools\SkeletonDancer\Configurator;
use Rollerworks\Tools\SkeletonDancer\QuestionsSet;

final class ComposerConfigurator implements Configurator
{
    public function interact(QuestionsSet $questions)
    {
    }

    public function finalizeConfiguration(array &$configuration)
    {
        $configuration['composer'] = array_merge(
            [
                'name' => $configuration['package_name'],
                'homepage' => 'http://www.park-manager.com',
                'description' => $configuration['module_description'],
                'license' => 'MPL-2.0',
                'authors' => [
                    [
                        'name' => $configuration['author_name'],
                        'email' => $configuration['author_email'],
                    ],
                    [
                        'name' => 'Community Contributors',
                        'homepage' => sprintf(
                            'https://github.com/park-manager/%s/graphs/contributors',
                            $configuration['module_name']
                        ),
                    ],
                ],
                'require' => ['park-manager/shared-kernel'],
                'autoload' => [
                    'psr-4' => [
                        $configuration['namespace'].'\\' => '',
                    ],
                ],
                'autoload-dev' => [
                    'psr-4' => [
                        $configuration['namespace'].'\\Tests\\' => 'Tests',
                    ],
                ],
            ],
            []
        );
    }
}
