<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\DomainName\Exception;

use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\Exception\TranslatableException;

final class DomainNameAlreadyInUse extends \DomainException implements TranslatableException
{
    private DomainNamePair $domainName;

    public function __construct(DomainNamePair $domainName)
    {
        parent::__construct(sprintf('DomainName "%s.%s" is already in use.', $domainName->name, $domainName->tld));

        $this->domainName = $domainName;
    }

    public function getTranslatorId(): string
    {
        return 'domain_name.already_in_use';
    }

    public function getTranslationArgs(): array
    {
        return [
            'name' => $this->domainName->name,
            'tld' => $this->domainName->tld,
        ];
    }
}
