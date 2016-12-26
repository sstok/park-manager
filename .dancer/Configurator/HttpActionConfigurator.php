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
use Rollerworks\Tools\SkeletonDancer\Question;
use Rollerworks\Tools\SkeletonDancer\QuestionsSet;

final class HttpActionConfigurator implements Configurator
{
    public function interact(QuestionsSet $questions)
    {
        $questions->communicate('action_name', Question::ask('Action name'));
        $questions->communicate('action_type', Question::choice('Action type', ['general', 'form', 'list'], 0));

        // Auto resolved.
        $questions->communicate('module_section', Question::ask('Module section', null, false));
        $questions->communicate('module_name', Question::ask('Module name'));
        $questions->communicate('namespace', Question::ask('Namespace'));
    }

    public function finalizeConfiguration(array &$configuration)
    {
        // no-op
    }
}
