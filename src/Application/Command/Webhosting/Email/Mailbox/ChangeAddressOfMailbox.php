<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Email\Mailbox;

use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\Webhosting\Email\MailboxId;
use ParkManager\Infrastructure\Validator\Constraints\EmailboxName;
use ParkManager\Infrastructure\Validator\Constraints\Webhosting\DomainNameOfSpace;

#[DomainNameOfSpace(spaceProperty: '@id.space')]
final class ChangeAddressOfMailbox
{
    public function __construct(
        public MailboxId $id,

        #[EmailboxName()]
        public string $address,

        public null | DomainNameId | DomainNamePair $domainName = null,
    ) {}
}
