<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\DomainName\Exception;

use DomainException;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\Exception\TranslatableException;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class CannotAssignDomainNameWithDifferentOwner extends DomainException implements TranslatableException
{
    private DomainNamePair $domainName;
    private ?SpaceId $current;
    private SpaceId $new;

    public function __construct(DomainNamePair $domainName, ?SpaceId $current, SpaceId $new)
    {
        parent::__construct(
            \sprintf(
                'Domain name "%s" of space %s does not have the same owner as space %s.',
                $domainName->toString(),
                $current ? $current->toString() : '[none]',
                $new->toString()
            )
        );

        $this->domainName = $domainName;
        $this->current = $current;
        $this->new = $new;
    }

    public function getTranslatorId(): string
    {
        return 'cannot_assign_domain_name_with_different_owner';
    }

    public function getTranslationArgs(): array
    {
        return [
            'domain_name' => $this->domainName->name,
            'domain_tld' => $this->domainName->tld,
            'current_space' => $this->current ? $this->current->toString() : '',
            'new_space' => $this->new->toString(),
        ];
    }
}
