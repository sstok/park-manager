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

namespace ParkManager\Component\Core\Model;

use ParkManager\Component\Core\Model\Event\AdministratorNameWasChanged;
use ParkManager\Component\Core\Model\Event\AdministratorWasRegistered;
use ParkManager\Component\User\Model\User;
use ParkManager\Component\User\Model\UserId;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 *
 * @final
 */
class Administrator extends User
{
    /**
     * @var string
     */
    private $firstName;

    /**
     * @var string
     */
    private $lastName;

    public static function registerWith(
        UserId $id,
        string $email,
        string $canonicalEmail,
        string $firstName,
        string $lastName,
        ?string $password = null
    ): self {
        $user = new static($id, $email, $canonicalEmail);
        $user->firstName = $firstName;
        $user->lastName = $lastName;

        $user->recordThat(new AdministratorWasRegistered($id, $email, $firstName, $lastName));
        $user->changePassword($password);

        return $user;
    }

    public function changeName(string $firstName, string $lastName): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->recordThat(new AdministratorNameWasChanged($this->id(), $firstName, $lastName));
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    protected static function getDefaultRoles(): array
    {
        return [self::DEFAULT_ROLE, 'ROLE_ADMIN'];
    }
}
