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
use ParkManager\Domain\ResultSet;
use ParkManager\Domain\User\UserId;

final class CannotRemoveActiveUser extends InvalidArgumentException implements TranslatableException
{
    public UserId $id;

    /**
     * @var array<string, ResultSet<object>>
     *
     * @see \ParkManager\Application\Service\OwnershipUsageList::getByProvider
     */
    public array $entities;

    /**
     * @param array<string, ResultSet<object>> $result
     */
    public function __construct(UserId $id, array $result)
    {
        parent::__construct(\sprintf('User with id "%s" cannot be removed as they are still assigned as owner to one or more entities.', $id->toString()));

        $this->id = $id;
        $this->entities = $result;
    }

    public function getTranslatorId(): string
    {
        return 'cannot_remove_active_user';
    }

    public function getTranslationArgs(): array
    {
        return [];
    }
}
