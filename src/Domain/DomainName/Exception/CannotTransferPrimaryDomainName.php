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

final class CannotTransferPrimaryDomainName extends DomainException implements TranslatableException
{
    private DomainNamePair $domainName;
    private SpaceId $current;
    private ?SpaceId $new;

    public function __construct(DomainNamePair $domainName, SpaceId $current, ?SpaceId $new)
    {
        parent::__construct(
            sprintf(
                'Domain name "%s" of space %s is marked as primary and cannot be transferred to space %s.',
                $domainName->toString(),
                $current->toString(),
                $new ? $new->toString() : '[owner]'
            )
        );

        $this->domainName = $domainName;
        $this->current = $current;
        $this->new = $new;
    }

    public function getTranslatorId(): string
    {
        return 'domain_name.cannot_transfer_space_primary_domain_name';
    }

    public function getTranslationArgs(): array
    {
        return [
            'domain_name' => $this->domainName->name,
            'domain_tld' => $this->domainName->tld,
            'current_space' => $this->current->toString(),
            'new_space' => $this->new ? $this->new->toString() : '',
        ];
    }
}
