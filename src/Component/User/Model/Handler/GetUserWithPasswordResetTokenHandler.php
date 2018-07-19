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

namespace ParkManager\Component\User\Model\Handler;

use ParkManager\Component\User\Model\Query\GetUserByPasswordResetToken;
use ParkManager\Component\User\ReadModel\User;
use ParkManager\Component\User\ReadModel\UserFinder;

final class GetUserWithPasswordResetTokenHandler
{
    private $userFinder;

    public function __construct(UserFinder $userFinder)
    {
        $this->userFinder = $userFinder;
    }

    public function __invoke(GetUserByPasswordResetToken $query): ?User
    {
        return $this->userFinder->findByPasswordResetToken($query->token()->selector());
    }
}
