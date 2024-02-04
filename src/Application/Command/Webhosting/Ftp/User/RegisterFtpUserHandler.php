<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Ftp\User;

use Lifthill\Component\Common\Application\PasswordHasher;
use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\Webhosting\Ftp\FtpUser;
use ParkManager\Domain\Webhosting\Ftp\FtpUserRepository;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;

final class RegisterFtpUserHandler
{
    public function __construct(
        private SpaceRepository $spaceRepository,
        private FtpUserRepository $userRepository,
        private DomainNameRepository $domainNameOrmRepository,
        private PasswordHasher $passwordHasher,
    ) {}

    public function __invoke(RegisterFtpUser $command): void
    {
        $space = $this->spaceRepository->get($command->space);
        $domainName = $this->domainNameOrmRepository->get($command->domainName);
        $password = $this->passwordHasher->hash($command->password);

        $user = new FtpUser($command->id, $space, $command->username, $password, $domainName, $command->homeDir);

        $this->userRepository->save($user);
    }
}
