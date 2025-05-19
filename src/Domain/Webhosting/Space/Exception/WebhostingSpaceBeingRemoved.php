<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Space\Exception;

use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParkManager\Domain\Exception\DomainError;
use ParkManager\Domain\Translation\TranslatableMessage;

final class WebhostingSpaceBeingRemoved extends \RuntimeException implements DomainError
{
    public function __construct(public DomainNamePair $name)
    {
        parent::__construct(
            \sprintf(
                'Webhosting space %s is currently being removed (or is marked for removal) and cannot be updated or changed.',
                $name->toString()
            )
        );
    }

    public function getTranslatorMsg(): TranslatableMessage
    {
        return new TranslatableMessage(
            'webhosting_space.is_being_removed',
            [
                'domain_name' => $this->name->toString(),
            ],
            'validators'
        );
    }

    public function getPublicMessage(): string
    {
        return 'Webhosting space {name} is currently being removed (or is marked for removal) and cannot be updated or changed.';
    }
}
