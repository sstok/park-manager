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

use ParkManager\Component\User\Exception\EmailChangeConfirmationRejected;
use ParkManager\Component\User\Model\Command\ConfirmEmailAddressChange;
use ParkManager\Component\User\Model\UserCollection;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class ConfirmEmailAddressChangeHandler
{
    private $userCollection;

    public function __construct(UserCollection $userCollection)
    {
        $this->userCollection = $userCollection;
    }

    public function __invoke(ConfirmEmailAddressChange $command): void
    {
        $token = $command->token();
        $success = false;

        if (null !== ($user = $this->userCollection->findByEmailAddressChangeToken($token->selector()))) {
            $success = $user->confirmEmailAddressChange($token);

            // Always save, as the token is cleared.
            //
            // It's still possible the e-mail address was already 'assigned' to someone else.
            // However this risk is rather small and handled by the repository constraints.
            $this->userCollection->save($user);
        }

        if (!$success) {
            throw new EmailChangeConfirmationRejected();
        }
    }
}
