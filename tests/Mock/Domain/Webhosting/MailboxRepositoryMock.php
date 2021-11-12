<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain\Webhosting;

use Closure;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\ResultSet;
use ParkManager\Domain\Webhosting\Email\Exception\AddressAlreadyExists;
use ParkManager\Domain\Webhosting\Email\Exception\MailboxNotFound;
use ParkManager\Domain\Webhosting\Email\Mailbox;
use ParkManager\Domain\Webhosting\Email\MailboxId;
use ParkManager\Domain\Webhosting\Email\MailboxRepository;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Mock\Domain\MockRepository;

/** @internal */
final class MailboxRepositoryMock implements MailboxRepository
{
    /** @use MockRepository<Mailbox> */
    use MockRepository;

    public const ID1 = '61c957ca-a74f-48ce-843a-a6adc9af2d62';

    /**
     * @return array<string, string|Closure>
     */
    protected function getFieldsIndexMapping(): array
    {
        return [
            'full_address' => static fn (Mailbox $mailbox): string => sprintf('%s@%s', $mailbox->address, $mailbox->domainName->toString()),
        ];
    }

    /**
     * @return array<string, string|Closure>
     */
    protected function getFieldsIndexMultiMapping(): array
    {
        return [
            'space_id' => static fn (Mailbox $mailbox): string => $mailbox->space->id->toString(),
        ];
    }

    public function get(MailboxId $id): Mailbox
    {
        return $this->mockDoGetById($id);
    }

    public function getByName(string $address, DomainNamePair $domainNamePair): Mailbox
    {
        return $this->mockDoGetByField('full_address', $address . '@' . $domainNamePair->toString());
    }

    public function hasName(string $address, DomainNamePair $domainNamePair): bool
    {
        try {
            $this->getByName($address, $domainNamePair);

            return true;
        } catch (MailboxNotFound) {
            return false;
        }
    }

    public function allBySpace(SpaceId $space): ResultSet
    {
        return $this->mockDoGetMultiByField('space_id', $space->toString());
    }

    public function countBySpace(SpaceId $space): int
    {
        return \count([...$this->mockDoGetMultiByField('space_id', $space->toString())]);
    }

    public function save(Mailbox $mailbox): void
    {
        try {
            if ($mailbox->addressChanged) {
                try {
                    if ($this->getByName($mailbox->address, $mailbox->domainName->namePair) !== $mailbox) {
                        throw new AddressAlreadyExists($mailbox->address, $mailbox->domainName->namePair);
                    }
                } catch (MailboxNotFound) {
                    // No-op.
                }
            }
        } catch (MailboxNotFound) {
            // No-op
        }

        $this->mockDoSave($mailbox);
    }

    public function remove(Mailbox $mailbox): void
    {
        $this->mockDoRemove($mailbox);
    }

    protected function throwOnNotFound(mixed $key): void
    {
        if ($key instanceof MailboxId) {
            throw MailboxNotFound::withId($key);
        }

        throw MailboxNotFound::withName($key);
    }
}
