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

namespace ParkManager\Component\User\Canonicalizer;

final class SimpleEmailCanonicalizer implements Canonicalizer
{
    public function canonicalize(string $string): string
    {
        return mb_convert_case($string, MB_CASE_LOWER, mb_detect_encoding($string, mb_detect_order(), true));
    }
}
