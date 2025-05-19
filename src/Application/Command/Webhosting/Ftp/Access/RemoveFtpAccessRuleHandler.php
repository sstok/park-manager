<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Ftp\Access;

use ParkManager\Domain\Webhosting\Ftp\AccessRuleRepository;

final class RemoveFtpAccessRuleHandler
{
    public function __construct(private AccessRuleRepository $ruleRepository)
    {
    }

    public function __invoke(RemoveFtpAccessRule $command): void
    {
        $rule = $this->ruleRepository->get($command->id);
        $this->ruleRepository->remove($rule);
    }
}
