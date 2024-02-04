<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Email\Mailbox;

use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\Webhosting\Email\Exception\AddressAlreadyExists;
use ParkManager\Domain\Webhosting\Email\ForwardRepository;
use ParkManager\Domain\Webhosting\Email\MailboxRepository;

final class ChangeAddressOfMailboxHandler
{
    public function __construct(
        private MailboxRepository $mailboxRepository,
        private ForwardRepository $forwardRepository,
        private DomainNameRepository $domainNameRepository,
    ) {}

    public function __invoke(ChangeAddressOfMailbox $command): void
    {
        $mailbox = $this->mailboxRepository->get($command->id);
        $domainName = $command->domainName ? $this->domainNameRepository->get($command->domainName) : $mailbox->domainName;

        if ($command->address === $mailbox->address && $domainName === $mailbox->domainName) {
            return;
        }

        if ($this->forwardRepository->hasName($command->address, $domainName->namePair)) {
            throw new AddressAlreadyExists($command->address, $domainName->namePair);
        }

        $mailbox->setAddress($command->address, $domainName);

        $this->mailboxRepository->save($mailbox);
    }
}
