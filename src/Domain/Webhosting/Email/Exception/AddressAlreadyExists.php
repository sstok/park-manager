<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Email\Exception;

use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParkManager\Domain\Exception\DomainError;
use ParkManager\Domain\Translation\TranslatableMessage;

final class AddressAlreadyExists extends \DomainException implements DomainError
{
    public function __construct(public string $name, public DomainNamePair $domainName)
    {
        parent::__construct(sprintf('Address %s.%s already exists.', $name, $domainName->toString()));
    }

    public function getTranslatorMsg(): TranslatableMessage
    {
        return new TranslatableMessage('email.address_already_exists', [
            'domain_name' => $this->domainName->toString(),
            'name' => $this->name,
        ], 'validators');
    }

    public function getPublicMessage(): string
    {
        return 'Address { name }.{ domain_name } already exists.';
    }
}
