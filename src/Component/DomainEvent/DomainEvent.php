<?php

declare(strict_types=1);

/*
 * This file is part of the Park-Manager project.
 *
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ParkManager\Component\DomainEvent;

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
