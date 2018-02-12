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

use ParkManager\Component\User\Model\Command\ChangeUserPassword;
use ParkManager\Component\User\Model\UserCollection;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class ChangeUserPasswordHandler
{
    private $userCollection;

    public function __construct(UserCollection $userCollection)
    {
        $this->userCollection = $userCollection;
    }

    public function __invoke(ChangeUserPassword $command): void
    {
        if (null !== ($user = $this->userCollection->get($command->id()))) {
            $user->changePassword($command->password());
            $this->userCollection->save($user);
        }
    }
}
