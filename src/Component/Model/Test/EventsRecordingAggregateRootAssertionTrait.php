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

namespace ParkManager\Component\Model\Test;

use ParkManager\Component\Model\DomainEvent;
use ParkManager\Component\Model\EventsRecordingAggregateRoot;
use ParkManager\Component\Model\Util\EventsExtractor;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
trait EventsRecordingAggregateRootAssertionTrait
{
    /**
     * @param EventsRecordingAggregateRoot $aggregateRoot
     * @param string|int                   $expectedId
     * @param DomainEvent[]                $expectedEvents
     */
    protected static function assertDomainEvents(EventsRecordingAggregateRoot $aggregateRoot, $expectedId, array $expectedEvents): void
    {
        $extractor = EventsExtractor::newInstance();
        $events = $extractor->extractDomainEvents($aggregateRoot);

        foreach ($expectedEvents as $i => $event) {
            self::assertArrayHasKey($i, $events, 'Event must exist at position.');
            self::assertEquals(get_class($events[$i]), get_class($event), 'Event at position must be of same type');
            self::assertEquals($events[$i]->payload(), $event->payload(), 'Event payload at position must be the same');
            self::assertEquals(
                $expectedId,
                $id = $events[$i]->entityId(),
                sprintf('Expected event "%s" id ("%s") to equal "%s"', get_class($event), $id, $expectedId)
            );

            DomainMessageAssertion::assertGettersEqualAfterEncoding($event);
        }

        self::assertCount($c = count($expectedEvents), $events, sprintf('Expected exactly "%d" events.', $c));
    }

    protected static function assertNoDomainEvents(EventsRecordingAggregateRoot $aggregateRoot): void
    {
        $extractor = EventsExtractor::newInstance();
        $events = $extractor->extractDomainEvents($aggregateRoot);

        self::assertCount(0, $events, sprintf('Expected exactly no events.'));
    }

    protected static function resetDomainEvents(EventsRecordingAggregateRoot ...$aggregateRoots): void
    {
        $extractor = EventsExtractor::newInstance();

        foreach ($aggregateRoots as $aggregateRoot) {
            $extractor->extractDomainEvents($aggregateRoot);
        }
    }
}
