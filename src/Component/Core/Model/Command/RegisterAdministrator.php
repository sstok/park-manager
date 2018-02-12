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

use ParkManager\Component\User\Model\UserId;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class RegisterAdministrator
{
    private $id;
    private $email;
    private $firstName;
    private $lastName;
    private $password;

    /**
     * Constructor.
     *
     * @param string      $id
     * @param string      $email
     * @param string      $firstName
     * @param string      $lastName
     * @param null|string $password  Null (no password) or an encoded password string (not plain)
     */
    public function __construct(string $id, string $email, string $firstName, string $lastName, ?string $password = null)
    {
        $this->id = UserId::fromString($id);
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->password = $password;
    }

    public function id(): UserId
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

    public function password(): ?string
    {
        return $this->password;
    }
}
