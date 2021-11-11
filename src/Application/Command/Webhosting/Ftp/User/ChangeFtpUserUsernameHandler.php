<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Ftp\User;

use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\Webhosting\Ftp\FtpUserRepository;

final class ChangeFtpUserUsernameHandler
{
    public function __construct(
        private FtpUserRepository $userRepository,
        private DomainNameRepository $domainNameOrmRepository,
    ) {
    }

    public function __invoke(ChangeFtpUserUsername $command): void
    {
        $user = $this->userRepository->get($command->id);
        $domainName = $command->domainName !== null ? $this->domainNameOrmRepository->get($command->domainName) : null;

        $user->changeUsername($command->username, $domainName);

        $this->userRepository->save($user);
    }
}
