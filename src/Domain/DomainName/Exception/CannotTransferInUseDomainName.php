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

final class CannotTransferInUseDomainName extends DomainException implements TranslatableException
{
    private DomainNamePair $domainName;
    private SpaceId $current;
    private string $type;
    private string $id;

    public function __construct(DomainNamePair $domainName, SpaceId $current, string $type, string $id)
    {
        parent::__construct(
            \sprintf(
                'Domain name "%s" of space %s is still in use by "%s: %s" and cannot be transferred.',
                $domainName->toString(),
                $current->toString(),
                $type,
                $id,
            )
        );

        $this->domainName = $domainName;
        $this->current = $current;
        $this->type = $type;
        $this->id = $id;
    }

    public function getTranslatorId(): string
    {
        return 'cannot_transfer_space_domain_name.used_by_' . $this->type;
    }

    public function getTranslationArgs(): array
    {
        return [
            'domain_name' => $this->domainName->name,
            'domain_tld' => $this->domainName->tld,
            'current_space' => $this->current->toString(),
            'id' => $this->id,
        ];
    }
}
