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

use Rollerworks\Tools\SkeletonDancer\OrderedConfigurator;
use Rollerworks\Tools\SkeletonDancer\Question;
use Rollerworks\Tools\SkeletonDancer\QuestionsSet;

final class ModuleConfigurator implements OrderedConfigurator
{
    public function interact(QuestionsSet $questions)
    {
        $questions->communicate('module_name', Question::ask('Module name', null, function ($value) {
            if ('' === (string) $value) {
                throw new \InvalidArgumentException('A value is required.');
            }

            return strtolower($value);
        }));
        $questions->communicate('module_description', Question::ask(
                'Module description',
                function (array $config) {
                    return sprintf('Park-Manager %s', $config['module_name']);
                }
            )
        );

        $questions->communicate('vendor_prefix', Question::ask('Vendor prefix'));
    }

    public function finalizeConfiguration(array &$configuration)
    {
        $configuration['git']['ignore'] = [
            '/vendor/',
            '*.phar',
            'phpunit.xml',
            'composer.lock',
        ];

        $configuration['git']['export-ignore'] = [
            '/Tests',
            '.gitignore',
            '.gitattributes',
            'phpunit.xml.dist',
        ];
    }

    /**
     * Returns the order of this generator.
     *
     * It ensures that this generator can check and use
     * the already generated structure.
     *
     * @return int
     */
    public function getOrder(): int
    {
        return -10;
    }
}
