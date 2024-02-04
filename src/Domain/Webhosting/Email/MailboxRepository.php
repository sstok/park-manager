<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Email;

use Lifthill\Component\Common\Domain\Attribute\Repository;
use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use Lifthill\Component\Common\Domain\ResultSet;
use ParkManager\Domain\Webhosting\Email\Exception\MailboxNotFound;
use ParkManager\Domain\Webhosting\Space\SpaceId;

#[Repository]
interface MailboxRepository
{
    /**
     * @throws MailboxNotFound
     */
    public function get(MailboxId $id): Mailbox;

    /**
     * @throws MailboxNotFound
     */
    public function getByName(string $address, DomainNamePair $domainNamePair): Mailbox;

    public function hasName(string $address, DomainNamePair $domainNamePair): bool;

    /**
     * @return ResultSet<Mailbox>
     */
    public function allBySpace(SpaceId $space): ResultSet;

    public function countBySpace(SpaceId $space): int;

    public function save(Mailbox $mailbox): void;

    public function remove(Mailbox $mailbox): void;
}
