<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\DomainName\Exception;

use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\Exception\DomainError;
use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class CannotRemovePrimaryDomainName extends \DomainException implements DomainError
{
    public function __construct(public DomainNameId $domainName, public SpaceId $space)
    {
        parent::__construct(
            \sprintf(
                'DomainName "%s" of space %s is marked as primary and cannot be removed.',
                $domainName->toString(),
                $space->toString()
            )
        );
    }

    public function getTranslatorMsg(): TranslatableMessage
    {
        return new TranslatableMessage('cannot_remove_space_primary_domain_name', [
            'domain_name' => $this->domainName,
            'space_id' => $this->space,
        ], 'validators');
    }

    public function getPublicMessage(): string
    {
        return 'DomainName "{domainName}" of space "{space}" is marked as primary and cannot be removed.';
    }
}
