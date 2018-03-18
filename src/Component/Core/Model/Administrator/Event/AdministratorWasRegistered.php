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

namespace ParkManager\Component\Core\Model\Administrator\Event;

use ParkManager\Component\Core\Model\Administrator\AdministratorId;
use ParkManager\Component\Model\Event\DomainEvent;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class AdministratorWasRegistered extends DomainEvent
{
    private $id;
    private $email;
    private $firstName;
    private $lastName;

    public function __construct(AdministratorId $id, string $email, string $firstName, string $lastName)
    {
        $this->id = $id;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function id(): AdministratorId
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }
}
