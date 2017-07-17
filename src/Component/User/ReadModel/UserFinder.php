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

namespace ParkManager\Component\User\ReadModel;

/**
 * A UserFinder provides (limited) information about a User
 * in a READ-ONLY format.
 *
 * Note: Unlike a Domain Repository these results are read-only
 * and are not automatically updated when the domain state changes.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
interface UserFinder
{
    /**
     * Returns the user's (limited) information by the password reset-token selector.
     *
     * This method is expected to return a User ReadModel with at least the following
     * fields populated: id, email, enabled, passwordResetToken.
     *
     * @param string $selector
     *
     * @return User|null
     */
    public function findByPasswordResetToken(string $selector): ?User;
}
