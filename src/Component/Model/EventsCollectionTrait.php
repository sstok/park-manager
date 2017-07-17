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

namespace ParkManager\Component\Model;

/**
 * The EventsCollectionTrait keeps track of recorded events.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
trait EventsCollectionTrait
{
    protected $domainEvents = [];

    protected function recordThat(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * @return DomainEvent[]
     */
    protected function popDomainEvents(): array
    {
        $pendingEvents = $this->domainEvents;
        $this->domainEvents = [];

        return $pendingEvents;
    }
}
