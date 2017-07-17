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

namespace ParkManager\Component\Core\Model\Command;

use ParkManager\Component\User\Model\Command\UserIdTrait;
use Prooph\Common\Messaging\Command;
use Prooph\Common\Messaging\PayloadTrait;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class RegisterAdministrator extends Command
{
    use PayloadTrait;
    use UserIdTrait;

    /**
     * Constructor.
     *
     * @param string      $id
     * @param string      $email
     * @param string      $firstName
     * @param string      $lastName
     * @param null|string $password  Null (no password) or
     *                               an encoded password string (not plain)
     */
    public function __construct(string $id, string $email, string $firstName, string $lastName, ?string $password = null)
    {
        $this->init();
        $this->setPayload([
            'id' => $id,
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'password' => $password,
        ]);
    }

    public function email(): string
    {
        return $this->payload['email'];
    }

    public function firstName(): string
    {
        return $this->payload['first_name'];
    }

    public function lastName(): string
    {
        return $this->payload['last_name'];
    }

    public function password(): ?string
    {
        return $this->payload['password'];
    }
}
