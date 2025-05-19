<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Ftp\Access;

use ParkManager\Domain\Webhosting\Ftp\AccessRule;
use ParkManager\Domain\Webhosting\Ftp\AccessRuleRepository;
use ParkManager\Domain\Webhosting\Ftp\FtpUserRepository;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;

final class AddFtpAccessRuleHandler
{
    public function __construct(
        private SpaceRepository $spaceRepository,
        private FtpUserRepository $userRepository,
        private AccessRuleRepository $ruleRepository,
    ) {
    }

    public function __invoke(AddFtpAccessRule $command): void
    {
        if ($command->entity instanceof SpaceId) {
            $space = $this->spaceRepository->get($command->entity);
            $rule = AccessRule::createForSpace($command->id, $space, $command->address, $command->strategy);
        } else {
            $user = $this->userRepository->get($command->entity);
            $rule = AccessRule::createForUser($command->id, $user, $command->address, $command->strategy);
        }

        $this->ruleRepository->save($rule);
    }
}
