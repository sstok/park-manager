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
use function basename;
use function getcwd;
use function mb_strpos;
use function mb_substr;
use function trim;

final class BundleQuestioner implements Questioner
{
    public function interact(QuestionsSet $questions)
    {
        $questions->communicate('bundle_name', Question::ask('Bundle name', basename(getcwd()), function ($value) {
            if (empty($value)) {
                throw new \InvalidArgumentException('Name cannot be empty.');
            }

            if (mb_substr($value, -6) === 'Bundle') {
                $value = mb_substr($value, 0, -6);
            }

            if (mb_strpos($value, 'Park-Manager') === 0) {
                $value = mb_substr($value, 12);
            }

            $value = trim($value);

            if ($value === '') {
                throw new \InvalidArgumentException('Name cannot be only "Bundle" or Park-Manager.');
            }

            return $value . 'Bundle';
        }));

        $questions->set('license', 'MIT');
        $questions->set('php_namespace', 'ParkManager\\Bundle\\' . StringUtil::camelize((string) $questions->get('bundle_name')));
        $questions->set('extension_name', StringUtil::camelize(mb_substr((string) $questions->get('bundle_name'), 0, -6)));
    }
}
