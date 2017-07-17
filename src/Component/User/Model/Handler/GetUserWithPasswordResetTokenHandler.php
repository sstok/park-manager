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

use ParkManager\Component\Model\CommandHandler;
use ParkManager\Component\User\Model\Query\GetUserByPasswordResetToken;
use ParkManager\Component\User\ReadModel\UserFinder;
use React\Promise\Deferred;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class GetUserWithPasswordResetTokenHandler implements CommandHandler
{
    private $userFinder;

    public function __construct(UserFinder $userFinder)
    {
        $this->userFinder = $userFinder;
    }

    public function __invoke(GetUserByPasswordResetToken $query, Deferred $deferred = null)
    {
        $user = $this->userFinder->findByPasswordResetToken($query->token()->selector());

        if (null === $deferred) {
            return $user;
        }

        $deferred->resolve($user);
    }
}
