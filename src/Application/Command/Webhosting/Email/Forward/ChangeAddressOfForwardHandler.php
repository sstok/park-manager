<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Email\Forward;

use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\Webhosting\Email\Exception\AddressAlreadyExists;
use ParkManager\Domain\Webhosting\Email\ForwardRepository;
use ParkManager\Domain\Webhosting\Email\MailboxRepository;

final class ChangeAddressOfForwardHandler
{
    public function __construct(
        private ForwardRepository $forwardRepository,
        private DomainNameRepository $domainNameRepository,
        private MailboxRepository $mailboxRepository,
    ) {
    }

    public function __invoke(ChangeAddressOfForward $command): void
    {
        $forward = $this->forwardRepository->get($command->id);
        $domainName = $command->domainName ? $this->domainNameRepository->get($command->domainName) : $forward->domainName;

        if ($command->address === $forward->address && $domainName === $forward->domainName) {
            return;
        }

        if ($this->mailboxRepository->hasName($command->address, $domainName->namePair)) {
            throw new AddressAlreadyExists($command->address, $domainName->namePair);
        }

        $forward->setAddress($command->address, $domainName);

        $this->forwardRepository->save($forward);
    }
}
