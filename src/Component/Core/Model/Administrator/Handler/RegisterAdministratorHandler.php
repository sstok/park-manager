<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Component\Core\Model\Administrator\Handler;

use ParkManager\Component\Core\Model\Administrator\Administrator;
use ParkManager\Component\Core\Model\Administrator\Command\RegisterAdministrator;
use ParkManager\Component\Core\Model\Administrator\Exception\AdministratorEmailAddressAlreadyInUse;
use ParkManager\Component\User\Canonicalizer\Canonicalizer;
use ParkManager\Component\User\Canonicalizer\SimpleEmailCanonicalizer;
use ParkManager\Component\User\Model\UserCollection;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class RegisterAdministratorHandler
{
    private $repository;
    private $emailCanonicalizer;

    public function __construct(UserCollection $repository, Canonicalizer $emailCanonicalizer = null)
    {
        $this->repository = $repository;
        $this->emailCanonicalizer = $emailCanonicalizer ?? new SimpleEmailCanonicalizer();
    }

    public function __invoke(RegisterAdministrator $command): void
    {
        $canonicalEmail = $this->emailCanonicalizer->canonicalize($email = $command->email());

        if (null !== $this->repository->findByEmailAddress($canonicalEmail)) {
            throw new AdministratorEmailAddressAlreadyInUse();
        }

        $this->repository->save(
            Administrator::registerWith($command->id(), $email, $canonicalEmail, $command->firstName(), $command->lastName(), $command->password())
        );
    }
}
