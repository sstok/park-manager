<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\SubDomain\Exception;

use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParkManager\Domain\Exception\DomainError;
use ParkManager\Domain\Translation\EntityLink;
use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\Webhosting\SubDomain\SubDomainNameId;

final class SubDomainAlreadyExists extends \DomainException implements DomainError
{
    public function __construct(public DomainNamePair $domainName, public string $name, private string $id)
    {
        parent::__construct(\sprintf('SubDomain "%s.%s" already exists.', $name, $domainName->toString()));
    }

    public function getTranslatorMsg(): TranslatableMessage
    {
        return new TranslatableMessage('subdomain.already_exists', [
            'domain_name' => $this->domainName->name,
            'domain_tld' => $this->domainName->tld,
            'name' => $this->name,
            'id' => new EntityLink(SubDomainNameId::fromString($this->id)),
        ], 'validators');
    }

    public function getPublicMessage(): string
    {
        return 'Sub-domain "{name}.{domainName}" already exists.';
    }
}
