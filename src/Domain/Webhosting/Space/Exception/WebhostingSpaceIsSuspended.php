<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Space\Exception;

use DomainException;
use ParkManager\Domain\Exception\TranslatableException;
use ParkManager\Domain\Translation\EntityLink;
use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Domain\Webhosting\Space\SuspensionLevel;

final class WebhostingSpaceIsSuspended extends DomainException implements TranslatableException
{
    private SpaceId $id;
    private SuspensionLevel $level;

    public function __construct(SpaceId $id, SuspensionLevel $level)
    {
        parent::__construct(sprintf('Webhosting Space "%s" is suspended with level %s', $id->toString(), $level->name));

        $this->id = $id;
        $this->level = $level;
    }

    public function getTranslatorId(): TranslatableMessage
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
}
