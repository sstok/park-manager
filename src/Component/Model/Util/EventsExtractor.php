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

namespace ParkManager\Component\Model\Util;

use ParkManager\Component\Model\DomainEvent;
use ParkManager\Component\Model\EventsRecordingAggregateRoot;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class EventsExtractor extends EventsRecordingAggregateRoot
{
    public static function newInstance()
    {
        return new self();
    }

    /**
     * @param EventsRecordingAggregateRoot $entity
     *
     * @return DomainEvent[]
     */
    public function extractDomainEvents(EventsRecordingAggregateRoot $entity): array
    {
        return $entity->popDomainEvents();
    }

    /**
     * @codeCoverageIgnore
     */
    public function id()
    {
        throw new \RuntimeException('No id was provided for this entity.');
    }
}
