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

namespace ParkManager\Component\ServiceBus;

use League\Tactician\CommandBus as ServiceBus;
use ParkManager\Component\ApplicationFoundation\Command\CommandBus;

final class TacticianCommandBus implements CommandBus
{
    private $bus;

    public function __construct(ServiceBus $bus)
    {
        $this->bus = $bus;
    }

    public function handle(object $query)
    {
        return $this->bus->handle($query);
    }
}
