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

namespace ParkManager\SkeletonDancer\Questioner;

use SkeletonDancer\Question;
use SkeletonDancer\Questioner;
use SkeletonDancer\QuestionsSet;
use SkeletonDancer\StringUtil;

final class ComponentQuestioner implements Questioner
{
    public function interact(QuestionsSet $questions)
    {
        $questions->communicate('component_name', Question::ask('Component name', basename(getcwd()), function ($value) {
            if (empty($value)) {
                throw new \InvalidArgumentException('Name cannot be empty.');
            }

            if ('Component' === mb_substr($value, -6)) {
                $value = mb_substr($value, 0, -6);
            }

            if (0 === mb_strpos($value, 'Park-Manager')) {
                $value = mb_substr($value, 12);
            }

            $value = trim($value);

            if ('' === $value) {
                throw new \InvalidArgumentException('Name cannot be only "Component" or Park-Manager.');
            }

            return $value;
        }));

        $questions->set('license', 'MPL-2.0');
        $questions->set('php_namespace', 'ParkManager\\Component\\'.StringUtil::camelize((string) $questions->get('component_name')));
    }
}
