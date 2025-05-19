<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Email\Mailbox;

use Lifthill\Component\Common\Application\PasswordHasher;
use ParkManager\Domain\Webhosting\Email\MailboxRepository;

final class ChangeMailboxPasswordHandler
{
    public function __construct(
        private MailboxRepository $repository,
        private PasswordHasher $passwordHasher,
    ) {
    }

    public function __invoke(ChangeMailboxPassword $command): void
    {
        $mailbox = $this->repository->get($command->id);
        $mailbox->changePassword($this->passwordHasher->hash($command->password));

        $this->repository->save($mailbox);
    }
}
