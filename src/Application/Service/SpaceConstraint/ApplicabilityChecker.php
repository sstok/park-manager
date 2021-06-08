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
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Email\MailboxRepository;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceRepository;

/**
 * Gets an applicable Constraints configuration for a space.
 *
 * If the Space current storage-usage is higher then the new limit a new limit is computed
 * of the current usage. The mailbox and web-quota allocation-size is left unchanged.
 *
 * All other constraints are set as-is. If current usage is higher than the new value,
 * the current items are preserved. Effectively meaning that when they are removed
 * the constraints restrict creation of new items.
 */
class ApplicabilityChecker
{
    public function __construct(
        private WebhostingSpaceRepository $spaceRepository,
        private MailboxRepository $mailboxRepository,
        private StorageUsage $storageUsageRetriever
    ) {
    }

    public function getApplicable(SpaceId $id, Constraints $constraints): Constraints
    {
        $space = $this->spaceRepository->get($id);
        $currentConstraints = $space->constraints;

        if ($currentConstraints->equals($constraints)) {
            return $currentConstraints;
        }

        $newConstraints = $currentConstraints->setMonthlyTraffic($constraints->monthlyTraffic);
        $newConstraints = $newConstraints->setEmail($newConstraints->email->mergeFrom($constraints->email));
        $newConstraints = $newConstraints->setDatabase($newConstraints->database->mergeFrom($constraints->database));

        return $this->computeMaximumStorageSize($constraints, $space, $newConstraints);
    }

    private function computeMaximumStorageSize(Constraints $toApply, Space $space, Constraints $newConstraints): Constraints
    {
        $currentConstraints = $space->constraints;

        if ($toApply->storageSize->greaterThanOrEqualTo($currentConstraints->storageSize)) {
            return $newConstraints->setStorageSize($toApply->storageSize);
        }

        // The new StorageSize needs to be equal or more than the current usage
        // Current usage is disk-usage of the space, plus all mailbox allocations.
        $currentSize = $this->storageUsageRetriever->getDiskUsageOf($space->id);
        $totalAllocation = $currentSize->increase($this->getTotalMailboxAllocation($space->id));

        // Note. This does not apply to web-quota, as this is managed by the user themself.
        if ($toApply->storageSize->lessThan($totalAllocation)) {
            $newConstraints = $newConstraints->setStorageSize($totalAllocation->increase(new ByteSize(1, 'MiB')));
        } else {
            $newConstraints = $newConstraints->setStorageSize($toApply->storageSize);
        }

        return $newConstraints;
    }

    private function getTotalMailboxAllocation(SpaceId $spaceId): ByteSize
    {
        $storageSize = new ByteSize(0, 'b');

        foreach ($this->mailboxRepository->allBySpace($spaceId) as $mailbox) {
            $storageSize = $storageSize->increase($mailbox->size);
        }

        return $storageSize;
    }
}
