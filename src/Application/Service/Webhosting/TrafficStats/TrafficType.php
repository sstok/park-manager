<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\Webhosting\TrafficStats;

use Lifthill\Component\Common\Domain\Model\Bitmask;

final class TrafficType extends Bitmask
{
    public const HTTP = 1;
    public const MAIL = 2;
    public const FTP = 4;

    public const ALL = self::HTTP | self::MAIL | self::FTP;
}
