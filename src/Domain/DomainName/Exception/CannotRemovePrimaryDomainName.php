<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\DomainName\Exception;

use DomainException;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\Exception\TranslatableException;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class CannotRemovePrimaryDomainName extends DomainException implements TranslatableException
{
    private DomainNameId $domainName;
    private SpaceId $spaceId;

    public function __construct(DomainNameId $domainName, SpaceId $spaceId)
    {
        parent::__construct(
            \sprintf(
                'Domain-name "%s" of space %s is marked as primary and cannot be removed.',
                $domainName->toString(),
                $spaceId->toString()
            )
        );

        $this->domainName = $domainName;
        $this->spaceId = $spaceId;
    }

    public function getTranslatorId(): string
    {
        return 'cannot_remove_space_primary_domain_name';
    }

    public function getTranslationArgs(): array
    {
        return [
            'domain_name' => $this->domainName->toString(),
            'space_id' => $this->spaceId->toString(),
        ];
    }
}
