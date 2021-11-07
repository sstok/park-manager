<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Type\Webhosting;

use ParkManager\Domain\Webhosting\Ftp\AccessRuleStrategy;
use ParkManager\Infrastructure\Doctrine\Type\EnumType;

final class FtpAccessRuleStrategyType extends EnumType
{
    public const NAME = 'park_manager_ftp_access_rule_strategy';
    public const OBJECT_CLASS = AccessRuleStrategy::class;
    public const ENUM_TYPE = 'integer';
}
