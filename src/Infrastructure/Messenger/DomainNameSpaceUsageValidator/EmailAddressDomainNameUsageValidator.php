<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Messenger\DomainNameSpaceUsageValidator;

use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\Exception\CannotTransferInUseDomainName;
use ParkManager\Domain\Webhosting\Email\Forward;
use ParkManager\Domain\Webhosting\Email\ForwardRepository;
use ParkManager\Domain\Webhosting\Email\Mailbox;
use ParkManager\Domain\Webhosting\Email\MailboxRepository;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Infrastructure\Messenger\DomainNameSpaceUsageValidator;

final class EmailAddressDomainNameUsageValidator implements DomainNameSpaceUsageValidator
{
    private MailboxRepository $mailboxRepository;
    private ForwardRepository $forwardRepository;

    public function __construct(MailboxRepository $mailboxRepository, ForwardRepository $forwardRepository)
    {
        $this->mailboxRepository = $mailboxRepository;
        $this->forwardRepository = $forwardRepository;
    }

    public function __invoke(DomainName $domainName, Space $space): void
    {
        /** @var Mailbox $mailbox */
        foreach ($this->mailboxRepository->allBySpace($space->id) as $mailbox) {
            if ($mailbox->domainName === $domainName) {
                new CannotTransferInUseDomainName($domainName->namePair, $space->id, 'mailbox', $mailbox->id->toString());
            }
        }

        /** @var Forward $forward */
        foreach ($this->forwardRepository->allBySpace($space->id) as $forward) {
            if ($forward->domainName === $domainName) {
                new CannotTransferInUseDomainName($domainName->namePair, $space->id, 'email_forward', $forward->id->toString());
            }
        }
    }
}
