<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Service\Webhosting;

use Doctrine\DBAL\Connection;
use Lifthill\Component\Common\Domain\Model\ByteSize;
use ParkManager\Application\Service\StorageUsage;
use ParkManager\Domain\Webhosting\Email\MailboxId;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class StorageUsageImpl implements StorageUsage
{
    public function __construct(private Connection $connection)
    {
    }

    public function getDiskUsageOf(SpaceId $id): ByteSize
    {
        return new ByteSize(1.7, 'gib');
    }

    public function getMailboxUsage(MailboxId $id): ByteSize
    {
        return new ByteSize(1, 'gib');
    }
}
