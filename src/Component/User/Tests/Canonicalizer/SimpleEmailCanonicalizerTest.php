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

namespace ParkManager\Component\User\Tests\Canonicalizer;

use ParkManager\Component\User\Canonicalizer\SimpleEmailCanonicalizer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SimpleEmailCanonicalizerTest extends TestCase
{
    /** @test */
    public function it_converts_strings_to_lower_case()
    {
        $canonicalizer = new SimpleEmailCanonicalizer();

        self::assertSame('teststring', $canonicalizer->canonicalize('TestString'));
        self::assertSame('teststrïng', $canonicalizer->canonicalize('TestStrïng'));
    }
}
