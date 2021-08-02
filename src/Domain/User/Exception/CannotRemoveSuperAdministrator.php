<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\User\Exception;

use InvalidArgumentException;
use ParkManager\Domain\Exception\DomainError;
use ParkManager\Domain\Translation\EntityLink;
use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\User\UserId;

final class CannotRemoveSuperAdministrator extends InvalidArgumentException implements DomainError
{
    public function __construct(public UserId $id)
    {
        parent::__construct(sprintf('User with id "%s" is a SuperAdmin and cannot be removed.', $id->toString()));
    }

    public function getTranslatorMsg(): TranslatableMessage
    {
        return new TranslatableMessage('cannot_remove_super_administrator', ['id' => new EntityLink($this->id)], 'validators');
    }

    public function getPublicMessage(): string
    {
        return 'User "{id}" is a SuperAdmin and cannot be removed.';
    }
}
