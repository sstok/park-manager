<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Ftp\Access;

use IPLib\Address\AddressInterface as IpAddress;
use IPLib\Range\RangeInterface as IpRange;
use ParkManager\Domain\Webhosting\Ftp\AccessRuleId;
use ParkManager\Domain\Webhosting\Ftp\AccessRuleStrategy;
use ParkManager\Domain\Webhosting\Ftp\FtpUserId;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class AddFtpAccessRule
{
    public function __construct(
        public AccessRuleId $id,
        public SpaceId | FtpUserId $entity,
        public IpRange | IpAddress $address,
        public ?AccessRuleStrategy $strategy = null,
    ) {
        $this->strategy ??= AccessRuleStrategy::get('DENY');
    }
}
