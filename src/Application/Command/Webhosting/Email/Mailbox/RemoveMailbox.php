<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Email\Mailbox;

use ParkManager\Domain\Webhosting\Email\MailboxId;

final class RemoveMailbox
{
    public function __construct(public MailboxId $id)
    {
    }
}
