<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\User\Exception;

use Lifthill\Component\Common\Domain\ResultSet;
use ParkManager\Domain\Exception\DomainError;
use ParkManager\Domain\Translation\EntityLink;
use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\User\UserId;

final class CannotRemoveActiveUser extends \InvalidArgumentException implements DomainError
{
    /**
     * @param array<class-string, ResultSet<object>> $entities
     *
     * @see \ParkManager\Application\Service\OwnershipUsageList::getByProvider
     */
    public function __construct(
        public UserId $id,
        public array $entities
    ) {
        parent::__construct(sprintf('User with id "%s" cannot be removed as they are still assigned as owner to one or more entities.', $id->toString()));
    }

    public function getTranslatorMsg(): TranslatableMessage
    {
        return new TranslatableMessage('cannot_remove_active_user', ['id' => new EntityLink($this->id)], 'validators');
    }

    public function getPublicMessage(): string
    {
        return 'User with id "%s" cannot be removed as they are still assigned as owner to one or more entities.';
    }
}
