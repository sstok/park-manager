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

namespace ParkManager\Component\User\Model\Command;

use ParkManager\Component\User\Model\UserId;

final class RequestConfirmationOfEmailAddressChange
{
    private $id;
    private $email;
    private $canonicalEmail;

    public function __construct(string $id, string $email, string $canonicalEmail)
    {
        $this->id = UserId::fromString($id);
        $this->email = $email;
        $this->canonicalEmail = $canonicalEmail;
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function canonicalEmail(): string
    {
        return $this->canonicalEmail;
    }
}
