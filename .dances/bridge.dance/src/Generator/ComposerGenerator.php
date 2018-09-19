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

use SkeletonDancer\StringUtil;
use function str_replace;

final class ComposerGenerator extends BaseComposerGenerator
{
    protected function generatePackageSubName(array $answers): string
    {
        return str_replace('_', '-', StringUtil::underscore($answers['bridge_name']));
    }

    protected function getType(): string
    {
        return 'library';
    }

    protected function getDescription(array $answers): string
    {
        return 'Park-Manager ' . $answers['bridge_name'] . ' Bridge';
    }

    protected function getRequires(): array
    {
        return [];
    }

    protected function getLicense(): string
    {
        return 'MIT';
    }
}
