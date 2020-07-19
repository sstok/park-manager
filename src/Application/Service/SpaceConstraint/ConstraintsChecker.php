<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\SpaceConstraint;

use ParkManager\Domain\ByteSize;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Webhosting\Constraint\Exception\ConstraintExceeded;
use ParkManager\Domain\Webhosting\Email\ForwardRepository;
use ParkManager\Domain\Webhosting\Email\MailboxId;
use ParkManager\Domain\Webhosting\Email\MailboxRepository;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceRepository;

class ConstraintsChecker
{
    private WebhostingSpaceRepository $spaceRepository;
    private MailboxRepository $mailboxRepository;
    private ForwardRepository $emailForwardRepository;

    public function __construct(WebhostingSpaceRepository $spaceRepository, MailboxRepository $mailboxRepository, ForwardRepository $emailForwardRepository)
    {
        $this->spaceRepository = $spaceRepository;
        $this->mailboxRepository = $mailboxRepository;
        $this->emailForwardRepository = $emailForwardRepository;
    }

    public function isStorageSizeExceeded(SpaceId $id): bool
    {
        return false; // XXX Needs storage-space provider
    }

    public function isTrafficQuotaExceeded(SpaceId $id): bool
    {
        return false; // XXX Needs traffic-usage provider
    }

    /**
     * @param array<string, ByteSize> $mailboxes
     */
    public function allowNewMailbox(SpaceId $id, array $mailboxes): void
    {
        $space = $this->spaceRepository->get($id);
        $emailConstraints = $space->constraints->email;

        if ($emailConstraints->maximumAddressCount > 0) {
            $totalCount = $this->mailboxRepository->countBySpace($id) +
                          $this->emailForwardRepository->countBySpace($id) +
                          \count($mailboxes);

            if ($totalCount > $emailConstraints->maximumAddressCount) {
                throw ConstraintExceeded::emailAddressesCount($emailConstraints->maximumAddressCount, $totalCount);
            }
        }

        if ($emailConstraints->maximumAddressCount === -1 || $emailConstraints->maximumMailboxCount === -1) {
            return;
        }

        $totalCount = $this->mailboxRepository->countBySpace($id) + \count($mailboxes);

        if ($totalCount > $emailConstraints->maximumMailboxCount) {
            throw ConstraintExceeded::mailboxCount($emailConstraints->maximumMailboxCount, $totalCount);
        }

        $this->checkNewMailboxSize($space, $mailboxes);
    }

    /**
     * @param array<string, ByteSize> $mailboxes
     */
    private function checkNewMailboxSize(Space $space, array $mailboxes): void
    {
        $maximumSize = $this->getMaximumMailboxSize($space);

        foreach ($mailboxes as $address => $size) {
            if ($size->value > $maximumSize) {
                throw ConstraintExceeded::mailboxStorageSizeRange(new EmailAddress($address), $size, new ByteSize(1, 'Mib'), $maximumSize);
            }

            $maximumSize = new ByteSize($maximumSize->value - $size->value, 'b');
        }
    }

    private function getMaximumMailboxSize(Space $space): ByteSize
    {
        // XXX Needs storage-space provider
        $storageSize = new ByteSize(100, 'Gib');

        $emailConstraints = $space->constraints->email;
        $maximumSize = $emailConstraints->maxStorageSize;

        if ($maximumSize->value > $storageSize->value || $maximumSize->value === ByteSize::inf()) {
            $maximumSize = $storageSize;
        }

        return $maximumSize;
    }

    public function allowNewEmailForward(SpaceId $id, array $forwards): void
    {
        $emailConstraints = $this->spaceRepository->get($id)->constraints->email;

        if ($emailConstraints->maximumAddressCount > 0) {
            $totalCount = $this->mailboxRepository->countBySpace($id) +
                          $this->emailForwardRepository->countBySpace($id) +
                          \count($forwards);

            if ($totalCount > $emailConstraints->maximumAddressCount) {
                throw ConstraintExceeded::emailAddressesCount($emailConstraints->maximumAddressCount, $totalCount);
            }
        }

        if ($emailConstraints->maximumAddressCount === -1 || $emailConstraints->maximumForwardCount === -1) {
            return;
        }

        $totalCount = $this->emailForwardRepository->countBySpace($id) + \count($forwards);

        if ($totalCount > $emailConstraints->maximumForwardCount) {
            throw ConstraintExceeded::emailForwardCount($emailConstraints->maximumMailboxCount, $totalCount);
        }
    }

    public function allowMailboxSize(MailboxId $id, ByteSize $size): void
    {
        $mailbox = $this->mailboxRepository->get($id);
        $emailConstraints = $mailbox->space->constraints->email;

        if ($mailbox->size->equals($size)) {
            return;
        }

        // XXX Needs storage-space provider
        $storageSize = new ByteSize(100, 'Gib');
        $maximumSize = $emailConstraints->maxStorageSize;

        if ($maximumSize->value > $storageSize->value || $maximumSize->value === ByteSize::inf()) {
            $maximumSize = $storageSize;
        }

        $currentSize = new ByteSize(10, 'GiB'); // XXX Needs Mailbox storage-size provider service

        if ($size->value > $emailConstraints->maxStorageSize || $size->value < $currentSize) {
            throw ConstraintExceeded::mailboxStorageSizeRange(new EmailAddress($mailbox->toString()), $size, $currentSize, $maximumSize);
        }
    }
}
