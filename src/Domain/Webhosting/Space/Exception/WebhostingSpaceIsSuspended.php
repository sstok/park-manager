<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Space\Exception;

use DomainException;
use ParkManager\Domain\Exception\DomainError;
use ParkManager\Domain\Translation\EntityLink;
use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Domain\Webhosting\Space\SuspensionLevel;

final class WebhostingSpaceIsSuspended extends DomainException implements DomainError
{
    public function __construct(public SpaceId $id, public SuspensionLevel $level)
    {
        parent::__construct(sprintf('Webhosting Space "%s" is suspended with level %s', $id->toString(), $level->name));
    }

    public function getTranslatorMsg(): TranslatableMessage
    {
        return new TranslatableMessage(
            'webhosting.space_is_suspended',
            [
                'id' => new EntityLink($this->id),
                'level' => new TranslatableMessage('webhosting_suspension_level' . mb_strtolower($this->level->name)),
            ],
            'validators'
        );
    }

    public function getPublicMessage(): string
    {
        return 'Webhosting Space "{id}" is suspended with level {level}.';
    }
}
