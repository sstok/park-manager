<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\DomainName;

use ParkManager\Domain\DomainName\DomainNamePair;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DomainNamePairTest extends TestCase
{
    /** @test */
    public function equatable(): void
    {
        $namePair = new DomainNamePair('example', 'com');

        self::assertTrue($namePair->equals($namePair));
        self::assertTrue($namePair->equals(clone $namePair));

        self::assertFalse($namePair->equals(new DomainNamePair('example', 'net')));
        self::assertFalse($namePair->equals(new DomainNamePair('park-manager', 'com')));
    }
}
