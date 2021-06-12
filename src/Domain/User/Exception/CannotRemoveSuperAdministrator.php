<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\User\Exception;

use InvalidArgumentException;
use ParkManager\Domain\Exception\TranslatableException;
use ParkManager\Domain\TranslatableMessage;
use ParkManager\Domain\User\UserId;

final class CannotRemoveSuperAdministrator extends InvalidArgumentException implements TranslatableException
{
    public UserId $id;

    public function __construct(UserId $id)
    {
        parent::__construct(sprintf('User with id "%s" is a SuperAdmin and cannot be removed.', $id->toString()));

        $this->id = $id;
    }

    public function getTranslatorId(): TranslatableMessage
    {
        return new TranslatableMessage('cannot_remove_super_administrator', [], 'validators');
    }
}
