<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Administrator;

use Carbon\CarbonImmutable;
use ParkManager\Domain\Organization\OrganizationId;
use ParkManager\Domain\Organization\OrganizationRepository;
use ParkManager\Domain\Owner;
use ParkManager\Domain\OwnerRepository;
use ParkManager\Domain\User\Exception\EmailAddressAlreadyInUse;
use ParkManager\Domain\User\Exception\UserNotFound;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserRepository;

final class RegisterAdministratorHandler
{
    public function __construct(
        private UserRepository $repository,
        private OwnerRepository $ownerRepository,
        private OrganizationRepository $organizationRepository
    ) {
    }

    public function __invoke(RegisterAdministrator $command): void
    {
        try {
            $user = $this->repository->getByEmail($command->email);

            throw new EmailAddressAlreadyInUse($user->id, $command->email);
        } catch (UserNotFound) {
            // No-op
        }

        $admin = User::registerAdmin(
            $command->id,
            $command->email,
            $command->displayName,
            $command->password
        );

        if ($command->superAdmin) {
            $admin->addRole('ROLE_SUPER_ADMIN');
        }

        if ($command->requireNewPassword) {
            $admin->expirePasswordOn(CarbonImmutable::rawParse('-1 year'));
        }

        $this->repository->save($admin);
        $this->ownerRepository->save(Owner::byUser($admin));

        $organization = $this->organizationRepository->get(OrganizationId::fromString(OrganizationId::ADMIN_ORG));
        $organization->addMember($admin);

        $this->organizationRepository->save($organization);
    }
}
