<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\UseCase\Administrator;

use ParkManager\Bundle\CoreBundle\Model\Administrator\Administrator;
use ParkManager\Bundle\CoreBundle\Model\Administrator\AdministratorRepository;
use ParkManager\Bundle\CoreBundle\Model\Administrator\Exception\AdministratorEmailAddressAlreadyInUse;
use ParkManager\Bundle\CoreBundle\Model\Administrator\Exception\AdministratorNotFound;

final class RegisterAdministratorHandler
{
    private $repository;

    public function __construct(AdministratorRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(RegisterAdministrator $command): void
    {
        try {
            $administrator = $this->repository->getByEmail($command->email);

            throw new AdministratorEmailAddressAlreadyInUse($administrator->getId());
        } catch (AdministratorNotFound $e) {
            // No-op
        }

        $this->repository->save(
            Administrator::register(
                $command->id,
                $command->email,
                $command->displayName,
                $command->password
            )
        );
    }
}
