<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Organization;

use ParkManager\Domain\Organization\Organization;
use ParkManager\Domain\Organization\OrganizationRepository;
use ParkManager\Domain\User\UserRepository;

final class RegisterOrganizationHandler
{
    public function __construct(
        private OrganizationRepository $organizationRepository,
        private UserRepository $userRepository
    ) {}

    public function __invoke(RegisterOrganization $command): void
    {
        $organization = new Organization($command->id, $command->name);
        $organization->addMember($this->userRepository->get($command->creator));

        $this->organizationRepository->save($organization);
    }
}
