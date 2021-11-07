<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Ftp\Exception;

use DomainException;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\Exception\DomainError;
use ParkManager\Domain\Translation\EntityLink;
use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\Webhosting\Ftp\FtpUserId;

final class FtpUsernameAlreadyExists extends DomainException implements DomainError
{
    public function __construct(public string $name, public DomainNamePair $domainName, private FtpUserId $id)
    {
        parent::__construct(sprintf('FTP username "%s.%s" already exists.', $name, $domainName->toString()));
    }

    public function getTranslatorMsg(): TranslatableMessage
    {
        return new TranslatableMessage('ftp.username_already_exists', [
            'domain_name' => $this->domainName->toString(),
            'name' => $this->name,
            'id' => new EntityLink($this->id),
        ], 'validators');
    }

    public function getPublicMessage(): string
    {
        return 'Username { name }.{ domain_name } is already in use for by user { id }.';
    }
}
