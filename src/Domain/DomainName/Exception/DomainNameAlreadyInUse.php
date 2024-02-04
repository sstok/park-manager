<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\DomainName\Exception;

use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParkManager\Domain\Exception\DomainError;
use ParkManager\Domain\Translation\TranslatableMessage;

final class DomainNameAlreadyInUse extends \DomainException implements DomainError
{
    public function __construct(public DomainNamePair $domainName)
    {
        parent::__construct(
            sprintf(
                'DomainName "%s.%s" is already in use.',
                $this->domainName->name,
                $this->domainName->tld
            )
        );
    }

    public function getTranslatorMsg(): TranslatableMessage
    {
        return new TranslatableMessage('domain_name.already_in_use', [
            'name' => $this->domainName->name,
            'tld' => $this->domainName->tld,
        ], 'validators');
    }

    public function getPublicMessage(): string
    {
        return 'DomainName "{domainName}" is already in use.';
    }
}
