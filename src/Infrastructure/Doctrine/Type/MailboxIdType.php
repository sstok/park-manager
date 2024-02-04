<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Type;

use Lifthill\Bridge\Doctrine\Attribute\DbalType;
use Lifthill\Bridge\Doctrine\Type\DomainIdType;
use ParkManager\Domain\Webhosting\Email\MailboxId;

#[DbalType('park_manager_webhosting_mailbox_id')]
final class MailboxIdType extends DomainIdType
{
    protected static function getIdClass(): string
    {
        return MailboxId::class;
    }
}
