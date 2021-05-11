<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\SpaceConstraint;

use ParkManager\Application\Service\StorageUsage;
use ParkManager\Domain\ByteSize;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Webhosting\Constraint\EmailConstraints;
use ParkManager\Domain\Webhosting\Constraint\Exception\ConstraintExceeded;
use ParkManager\Domain\Webhosting\Email\ForwardRepository as EmailForwardRepository;
use ParkManager\Domain\Webhosting\Email\MailboxId;
use ParkManager\Domain\Webhosting\Email\MailboxRepository;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceRepository;

class ConstraintsChecker
{
    private WebhostingSpaceRepository $spaceRepository;
    private MailboxRepository $mailboxRepository;
    private EmailForwardRepository $emailForwardRepository;
    private StorageUsage $storageUsageRetriever;

    public function __construct(WebhostingSpaceRepository $spaceRepository, MailboxRepository $mailboxRepository, EmailForwardRepository $emailForwardRepository, StorageUsage $storageUsageRetriever)
    {
        $this->spaceRepository = $spaceRepository;
        $this->mailboxRepository = $mailboxRepository;
        $this->emailForwardRepository = $emailForwardRepository;
        $this->storageUsageRetriever = $storageUsageRetriever;
    }

    public function isStorageSizeReached(SpaceId $id): bool
    {
        $constraints = $this->spaceRepository->get($id)->constraints;
        $totalSpaceUsage = $this->storageUsageRetriever->getDiskUsageOf($id);

        return $totalSpaceUsage->greaterThanOrEqualTo($constraints->storageSize);
    }

    /**
     * @param array<string, ByteSize> $mailboxes ['address' => {ByteSize}]
     *
     * @throws ConstraintExceeded
     */
    public function allowNewMailboxes(SpaceId $id, array $mailboxes): void
    {
        $space = $this->spaceRepository->get($id);
        $emailConstraints = $space->constraints->email;

        // Must be checked first as the maximum address-count prevails when set.
        if ($emailConstraints->maximumAddressCount > 0) {
            $this->checkNewAddressCountTotal($id, \count($mailboxes), $emailConstraints);
        } elseif ($emailConstraints->maximumAddressCount !== -1 && $emailConstraints->maximumMailboxCount !== -1) {
            // Note that `maximumAddressCount = -1` will fail the condition. And therefore is required.
            $totalCount = $this->mailboxRepository->countBySpace($id) + \count($mailboxes);

            if ($totalCount > $emailConstraints->maximumMailboxCount) {
                throw ConstraintExceeded::mailboxCount($emailConstraints->maximumMailboxCount, $totalCount);
            }
        }

        $this->checkNewMailboxSize($space, $mailboxes);
    }

    /**
     * @throws ConstraintExceeded
     */
    private function checkNewAddressCountTotal(SpaceId $id, int $amount, EmailConstraints $emailConstraints): void
    {
        $totalCount = $this->mailboxRepository->countBySpace($id) +
                      $this->emailForwardRepository->countBySpace($id) +
                      $amount;

        if ($totalCount > $emailConstraints->maximumAddressCount) {
            throw ConstraintExceeded::emailAddressesCount($emailConstraints->maximumAddressCount, $totalCount);
        }
    }

    /**
     * @param array<string, ByteSize> $mailboxes
     *
     * @throws ConstraintExceeded
     */
    private function checkNewMailboxSize(Space $space, array $mailboxes): void
    {
        $minSize = new ByteSize(1, 'Mib');
        $maximumSize = $this->getMaximumMailboxSize($space);

        foreach ($mailboxes as $address => $size) {
            // Precedence is important here. The size must not be less then min, if max is inf the greater size is ignored.
            if ($size->lessThan($minSize) || (! $maximumSize->isInf() && $size->greaterThan($maximumSize))) {
                throw ConstraintExceeded::mailboxStorageSizeRange(new EmailAddress($address), $size, $minSize, $maximumSize);
            }

            if (! $maximumSize->isInf()) {
                $maximumSize = $maximumSize->decrease($size);
            }
        }
    }

    private function getMaximumMailboxSize(Space $space): ByteSize
    {
        $maximumSize = $space->constraints->email->maxStorageSize;

        if ($maximumSize->isInf()) {
            $maximumSize = $space->constraints->storageSize;
        }

        if ($maximumSize->isInf()) {
            return $maximumSize;
        }

        $webStorageAllocation = $space->webQuota ?? $this->storageUsageRetriever->getDiskUsageOf($space->id);
        $mailboxAllocation = $this->getTotalMailboxAllocation($space->id);

        // First calculate how much of the total storage-size is allocated to web-storage.
        // What remains is usable for mailboxes.
        $totalFreeSpace = $space->constraints->storageSize->decrease($webStorageAllocation);

        // Now decrease with the current mailbox usage.
        $totalFreeSpace = $totalFreeSpace->decrease($mailboxAllocation);

        // Returns the lowest constrained possible value.
        //
        // If the total amount of free-space is less then the maximum, use that.
        // Otherwise we use the maximum-size, which is always less than what's free.
        return $totalFreeSpace->lessThan($maximumSize) ? $totalFreeSpace : $maximumSize;
    }

    private function getTotalMailboxAllocation(SpaceId $spaceId): ByteSize
    {
        $storageSize = new ByteSize(0, 'b');

        foreach ($this->mailboxRepository->allBySpace($spaceId) as $mailbox) {
            $storageSize = $storageSize->increase($mailbox->size);
        }

        return $storageSize;
    }

    /**
     * @param array<int|string, string> $forwards
     *
     * @throws ConstraintExceeded
     */
    public function allowNewEmailForward(SpaceId $id, array $forwards): void
    {
        $emailConstraints = $this->spaceRepository->get($id)->constraints->email;

        // Must be checked first as the maximum address-count prevails when set.
        if ($emailConstraints->maximumAddressCount > 0) {
            $this->checkNewAddressCountTotal($id, \count($forwards), $emailConstraints);

            return;
        }

        if ($emailConstraints->maximumAddressCount === -1 || $emailConstraints->maximumForwardCount === -1) {
            return;
        }

        $countBySpace = $this->emailForwardRepository->countBySpace($id);
        $totalCount = $countBySpace + \count($forwards);

        if ($totalCount > $emailConstraints->maximumForwardCount) {
            throw ConstraintExceeded::emailForwardCount($emailConstraints->maximumForwardCount, $totalCount);
        }
    }

    /**
     * @throws ConstraintExceeded
     */
    public function allowMailboxSize(MailboxId $id, ByteSize $requestedSize): void
    {
        $mailbox = $this->mailboxRepository->get($id);

        if ($mailbox->size->equals($requestedSize)) {
            return;
        }

        $currentSize = $this->storageUsageRetriever->getMailboxUsage($id);
        $maximumSize = $this->getMaximumMailboxSize($mailbox->space);

        if ($requestedSize->greaterThan($maximumSize) || $requestedSize->lessThan($currentSize)) {
            throw ConstraintExceeded::mailboxStorageResizeRange(
                new EmailAddress($mailbox->toString()),
                $requestedSize,
                $currentSize,
                $maximumSize
            );
        }
    }

    /**
     * @throws ConstraintExceeded
     */
    public function allowHostingSize(SpaceId $id, ByteSize $requestedSize): void
    {
        $space = $this->spaceRepository->get($id);
        $maximumSize = $space->constraints->storageSize;

        if ($maximumSize->isInf()) {
            return;
        }

        $currentSize = $this->storageUsageRetriever->getDiskUsageOf($space->id);

        $totalAllocation = $currentSize->increase($this->getTotalMailboxAllocation($space->id));
        $totalFreeSpace = $maximumSize->decrease($totalAllocation);

        if ($requestedSize->greaterThan($totalFreeSpace) || $requestedSize->lessThan($currentSize)) {
            throw ConstraintExceeded::diskStorageSizeRange(
                $id,
                $requestedSize,
                $currentSize,
                $totalFreeSpace
            );
        }
    }
}
