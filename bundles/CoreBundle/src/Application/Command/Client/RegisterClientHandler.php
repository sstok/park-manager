<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Application\Command\Client;

use ParkManager\Bundle\CoreBundle\Domain\Client\Client;
use ParkManager\Bundle\CoreBundle\Domain\Client\ClientRepository;

final class RegisterClientHandler
{
    /** @var ClientRepository */
    private $clientRepository;

    public function __construct(ClientRepository $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    public function __invoke(RegisterClient $command)
    {
        $this->clientRepository->save(
            Client::register(
                $command->id,
                $command->primaryEmail,
                $command->displayName,
                $command->password
            )
        );
    }
}
