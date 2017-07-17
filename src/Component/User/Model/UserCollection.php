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

namespace ParkManager\Component\User\Model;

use ParkManager\Component\User\Exception\UserNotFound;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
interface UserCollection
{
    /**
     * Get User by id.
     *
     * @param UserId $id
     *
     * @throws UserNotFound when no user was found with the id
     *
     * @return User
     */
    public function get(UserId $id);

    /**
     * @param string $email
     *
     * @return User|null
     */
    public function getByEmailAddress(string $email);

    /**
     * @param string $selector
     *
     * @return User|null
     */
    public function getsByEmailAddressChangeToken(string $selector);

    /**
     * @param string $selector
     *
     * @return User|null
     */
    public function getByPasswordResetToken(string $selector);

    /**
     * Save the User in the repository.
     *
     * This will either store a new user or update an existing one.
     *
     * @param User $user
     */
    public function save(User $user): void;

    /**
     * Remove a user registration from the repository.
     *
     * @param User $user
     */
    public function remove(User $user): void;
}
