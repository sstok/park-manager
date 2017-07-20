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

use PHPUnit\Framework\Assert;
use Prooph\Common\Messaging\DomainMessage;
use Prooph\Common\Messaging\Message;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class DomainMessageAssertion
{
    /**
     * Compare the result of getter methods to ensure there payload produced a correct result.
     *
     * For objects check if they are equatable, if not use assertEquals to help with debugging.
     *
     * @param DomainMessage $message
     */
    public static function assertGettersEqualAfterEncoding(DomainMessage $message): void
    {
        self::assertPayloadEncodable($message);

        $storedEventArray = $message->toArray();
        $storedEventArray['payload'] = json_decode(json_encode($message->payload(), \JSON_FORCE_OBJECT), true);
        $storedEvent = $message::fromArray($storedEventArray);

        foreach (self::findPublicEventMethods($message) as $method) {
            $result = $message->{$method}();
            $secondResult = $storedEvent->{$method}();

            if (is_object($result) && method_exists($result, 'equals') && $result->equals($secondResult)) {
                continue;
            }

            Assert::assertEquals($result, $secondResult);
        }
    }

    /**
     * Asserts the payload is encodable and produces the same result.
     *
     * Encodes then decodes to ensure no information was lost (objects cannot be json-encoded).
     *
     * @param Message $primaryMessage
     * @param string  $assertMessage
     */
    public static function assertPayloadEncodable(Message $primaryMessage, ?string $assertMessage = null): void
    {
        Assert::assertSame(
            $primaryMessage->payload(),
            json_decode(json_encode($primaryMessage->payload(), \JSON_FORCE_OBJECT), true),
            $assertMessage ?? 'Payload must be the same after encoding'
        );
    }

    private static function findPublicEventMethods(Message $event): iterable
    {
        foreach ((new \ReflectionObject($event))->getMethods(\ReflectionMethod::IS_PUBLIC) as $methodReflection) {
            if ($methodReflection->isStatic() || $methodReflection->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            if (0 === mb_strpos($methodReflection->getDeclaringClass()->getNamespaceName(), 'Prooph\\')) {
                continue;
            }

            yield $methodReflection->name;
        }
    }
}
