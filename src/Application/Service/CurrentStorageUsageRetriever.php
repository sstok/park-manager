<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service;

use ParkManager\Domain\ByteSize;
use ParkManager\Domain\Webhosting\Email\MailboxId;
use ParkManager\Domain\Webhosting\Space\SpaceId;

interface CurrentStorageUsageRetriever
{
    /**
     * Gets the total disk-space usage of a Space.
     */
    public function getDiskUsageOf(SpaceId $id): ByteSize;

    public function getMailboxUsage(MailboxId $id): ByteSize;
}
