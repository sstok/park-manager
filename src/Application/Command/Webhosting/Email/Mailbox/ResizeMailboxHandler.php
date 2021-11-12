<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Email\Mailbox;

use ParkManager\Application\Service\SpaceConstraint\ConstraintsChecker;
use ParkManager\Domain\Webhosting\Email\MailboxRepository;

final class ResizeMailboxHandler
{
    public function __construct(
        private MailboxRepository $repository,
        private ConstraintsChecker $constraintsChecker
    ) {
    }

    public function __invoke(ResizeMailbox $command): void
    {
        $this->constraintsChecker->allowMailboxSize($command->id, $command->size);

        $mailbox = $this->repository->get($command->id);
        $mailbox->resize($command->size);

        $this->repository->save($mailbox);
    }
}