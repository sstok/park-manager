<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Ftp\User;

use ParkManager\Domain\Webhosting\Ftp\FtpUserRepository;

final class EnableFtpUserHandler
{
    public function __construct(private FtpUserRepository $repository)
    {
    }

    public function __invoke(EnableFtpUser $command): void
    {
        $user = $this->repository->get($command->id);
        $user->enable();

        $this->repository->save($user);
    }
}
