<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Application\Service;

use Assert\Assertion;
use ParkManager\Application\Service\CurrentStorageUsageRetriever;
use ParkManager\Domain\ByteSize;
use ParkManager\Domain\Webhosting\Email\Exception\MailboxNotFound;
use ParkManager\Domain\Webhosting\Email\MailboxId;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceNotFound;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Mock\Domain\Webhosting\MailboxRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;

/**
 * @internal
 */
final class CurrentStorageUsageRetrieverMock implements CurrentStorageUsageRetriever
{
    /**
     * [$id => {ByteSize}].
     *
     * @var array<string,ByteSize>
     */
    private array $spaces;

    /**
     * [$id => {ByteSize}].
     *
     * @var array<string,ByteSize>
     */
    private array $mailboxes;

    /**
     * @param array<string,ByteSize>|null $spaces    [$id => {ByteSize}] or null for mock-example
     * @param array<string,ByteSize>|null $mailboxes [$id => {ByteSize}] or null for mock-example
     */
    public function __construct(?array $spaces = null, ?array $mailboxes = null)
    {
        $spaces ??= [SpaceRepositoryMock::ID1 => new ByteSize(100, 'MiB')];
        $mailboxes ??= [MailboxRepositoryMock::ID1 => new ByteSize(10, 'MiB')];

        Assertion::allIsInstanceOf($spaces, ByteSize::class);
        Assertion::allIsInstanceOf($mailboxes, ByteSize::class);

        $this->spaces = $spaces;
        $this->mailboxes = $mailboxes;
    }

    public function getDiskUsageOf(SpaceId $id): ByteSize
    {
        if (! isset($this->spaces[$id->toString()])) {
            throw WebhostingSpaceNotFound::withId($id);
        }

        return $this->spaces[$id->toString()];
    }

    public function getMailboxUsage(MailboxId $id): ByteSize
    {
        if (! isset($this->mailboxes[$id->toString()])) {
            throw MailboxNotFound::withId($id);
        }

        return $this->mailboxes[$id->toString()];
    }

    public function setDiskUsageOf(SpaceId $id, ByteSize $size): self
    {
        $this->spaces[$id->toString()] = $size;

        return $this;
    }

    public function setMailboxUsage(MailboxId $id, ByteSize $size): self
    {
        $this->mailboxes[$id->toString()] = $size;

        return $this;
    }
}
