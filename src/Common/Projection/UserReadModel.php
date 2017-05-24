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

namespace ParkManager\Common\Projection;

use ParkManager\Common\Model\Security\AuthenticationInfo;

/**
 * Shared ReadModel interface to be implemented by ReadModel
 * providing authentication information of single user.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
interface UserReadModel
{
    /**
     * Returns the assigned AuthenticationInfo of this User.
     *
     * @return AuthenticationInfo
     */
    public function authenticationInfo(): AuthenticationInfo;

    /**
     * Returns whether access for this user enabled.
     *
     * @return bool
     */
    public function isAccessEnabled(): bool;

    /**
     * Returns the unique id of this user.
     *
     * @return string UUID in string format
     */
    public function id(): string;
}
