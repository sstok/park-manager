<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Email\Forward;

use ParkManager\Domain\Webhosting\Email\ForwardRepository;

final class ActivateForwardHandler
{
    public function __construct(private ForwardRepository $repository) {}

    public function __invoke(ActivateForward $command): void
    {
        $forward = $this->repository->get($command->id);
        $forward->activate();

        $this->repository->save($forward);
    }
}
