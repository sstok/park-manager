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

namespace ParkManager\Component\Model\Tests;

use ParkManager\Component\Model\Tests\Mock\Event\UserWasRegistered;
use ParkManager\Component\Model\Tests\Mock\User;
use ParkManager\Component\Model\Util\EventsExtractor;
use PHPUnit\Framework\TestCase;

final class EventsRecordingEntityTest extends TestCase
{
    /** @test */
    public function it_records_domain_events()
    {
        $user = User::register('master');
        $user->changeName('missy');

        $extractor = EventsExtractor::newInstance();
        $domainEvents = $extractor->extractDomainEvents($user);

        self::assertCount(1, $domainEvents);
        self::assertArrayHasKey(0, $domainEvents);
        self::assertInstanceOf(UserWasRegistered::class, $domainEvents[0]);

        // Events must be cleared during retrieval.
        self::assertEquals([], $extractor->extractDomainEvents($user));
    }
}
