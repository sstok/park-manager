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

namespace ParkManager\Common\Test\Model;

use PHPUnit\Framework\TestCase;
use Prooph\EventSourcing\AggregateChanged;
use Prooph\EventSourcing\AggregateRoot;
use Prooph\EventSourcing\EventStoreIntegration\AggregateRootDecorator;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
abstract class AggregateRootTestCase extends TestCase
{
    /**
     * @param AggregateRoot      $aggregateRoot
     * @param AggregateChanged[] $expectedEvents
     */
    protected static function assertEventsAggregateEvents(AggregateRoot $aggregateRoot, array $expectedEvents, bool $ignoreFirst = true)
    {
        $extractor = AggregateRootDecorator::newInstance();
        $expectedId = $extractor->extractAggregateId($aggregateRoot);
        $events = $extractor->extractRecordedEvents($aggregateRoot);

        if ($ignoreFirst) {
            array_shift($events);
        }

        foreach ($expectedEvents as $i => $event) {
            self::assertArrayHasKey($i, $events, 'Event must exist at position.');
            self::assertEquals(get_class($events[$i]), get_class($event), 'Event at position must be of same type');
            self::assertEquals($events[$i]->payload(), $event->payload(), 'Event payload at position must be the same');
            self::assertEquals(
                $expectedId,
                $id = $events[$i]->aggregateId(),
                sprintf('Expected event "%s" aggregateId ("%s") to equal "%s"', get_class($event), $id, $expectedId)
            );

            // Now validate that a "stored" Event produces an equal result as a normal one (newly recorded).
            // - Encode then decode to ensure no information was lost (objects cannot be json-encoded).
            // - Compare the result of getter methods to ensure there payload produces a correct result.
            // -- For objects check if they are equatable, if not use assertEquals to help with debugging.

            $storedPayload = json_decode(json_encode($event->payload(), \JSON_FORCE_OBJECT), true);
            $storedEvent = $event::occur($expectedId, $storedPayload);

            self::assertEquals($events[$i]->payload(), $storedPayload, 'Event payload at position must be the same after encoding');

            foreach (self::findPublicEventMethods($event) as $method) {
                $result = $event->{$method}();
                $secondResult = $storedEvent->{$method}();

                if (is_object($result) && method_exists($result, 'equals') && $result->equals($secondResult)) {
                    continue;
                }

                self::assertEquals($result, $secondResult);
            }
        }

        self::assertCount($c = count($events), $events, sprintf('Expected exactly "%d" events.', $c));
    }

    private static function findPublicEventMethods(AggregateChanged $event): iterable
    {
        foreach ((new \ReflectionObject($event))->getMethods(\ReflectionMethod::IS_PUBLIC) as $methodReflection) {
            if ($methodReflection->isStatic() || $methodReflection->getNumberOfRequiredParameters() > 0) {
                 continue;
            }

            if (0 === strpos($methodReflection->getDeclaringClass()->getNamespaceName(), 'Prooph\\')) {
                continue;
            }

            yield $methodReflection->name;
        }
    }
}
