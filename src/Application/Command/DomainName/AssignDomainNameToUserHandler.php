<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\DomainName;

use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\User\UserRepository;

final class AssignDomainNameToUserHandler
{
    private DomainNameRepository $domainNameRepository;
    private UserRepository $userRepository;

    public function __construct(DomainNameRepository $domainNameRepository, UserRepository $userRepository)
    {
        $this->domainNameRepository = $domainNameRepository;
        $this->userRepository = $userRepository;
    }

    public function __invoke(AssignDomainNameToUser $command): void
    {
        $domainName = $this->domainNameRepository->get($command->id);
        $user = $this->userRepository->get($command->user);

        $domainName->transferToOwner($user);

        $this->domainNameRepository->save($domainName);
    }
}
