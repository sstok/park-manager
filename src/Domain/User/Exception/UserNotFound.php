<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\User\Exception;

use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Exception\NotFoundException;
use ParkManager\Domain\User\UserId;

final class UserNotFound extends NotFoundException
{
    public static function withId(UserId $userId): self
    {
        return new self(\sprintf('User with id "%s" does not exist.', $userId->toString()), ['{id}' => $userId->toString()]);
    }

    public static function withEmail(EmailAddress $address): self
    {
        return new self(\sprintf('User with email address "%s" does not exist.', $address->toString()), ['{email}' => $address->toString()]);
    }

    public function getTranslatorId(): string
    {
        if (isset($this->translationArgs['{id}'])) {
            return 'User with id "{id}" does not exist.';
        }

        return 'User with email address "{email}" does not exist.';
    }
}
