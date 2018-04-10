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

namespace ParkManager\Component\SharedKernel\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * A DomainEvent occurs when something changed within the Domain
 * that other systems are interested in.
 *
 * Note: This class functions as an adapter to the Symfony EventDispatcher.
 * The class-name is used as event name.
 */
abstract class DomainEvent extends Event
{
}
