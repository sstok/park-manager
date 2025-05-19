<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Email\Mailbox;

use ParkManager\Application\Service\SystemGateway;
use ParkManager\Application\Service\SystemGateway\Webhosting\RemoveMailbox as RemoveMailboxOpr;
use ParkManager\Domain\Webhosting\Email\MailboxRepository;

final class RemoveMailboxHandler
{
    public function __construct(
        private MailboxRepository $repository,
        private SystemGateway $systemGateway,
    ) {
    }

    public function __invoke(RemoveMailbox $command): void
    {
        $mailbox = $this->repository->get($command->id);

        $this->systemGateway->execute(new RemoveMailboxOpr($command->id));
        $this->repository->remove($mailbox);
    }
}
