<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\SubDomain\Exception;

use DomainException;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\Exception\TranslatableException;
use ParkManager\Domain\TranslatableMessage;

final class SubDomainAlreadyExists extends DomainException implements TranslatableException
{
    private DomainNamePair $domainName;
    private string $name;

    public function __construct(DomainNamePair $domainName, string $name, private string $id)
    {
        parent::__construct(sprintf('SubDomain "%s.%s" already exists.', $name, $domainName->toString()));

        $this->domainName = $domainName;
        $this->name = $name;
    }

    public function getTranslatorId(): TranslatableMessage
    {
        return new TranslatableMessage('subdomain.already_exists', [
            'domain_name' => $this->domainName->name,
            'domain_tld' => $this->domainName->tld,
            'name' => $this->name,
            'id' => $this->id,
        ], 'validators');
    }
}
