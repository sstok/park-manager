<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Email\Forward;

use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Webhosting\Email\ForwardRepository;

final class ChangeDestinationOfForwardHandler
{
    public function __construct(private ForwardRepository $forwardRepository)
    {
    }

    public function __invoke(ChangeDestinationOfForward $command): void
    {
        $forward = $this->forwardRepository->get($command->id);

        if ($command->destination instanceof EmailAddress) {
            $forward->setDestinationToAddress($command->destination);
        } else {
            $forward->setDestinationToScript($command->destination);
        }

        $this->forwardRepository->save($forward);
    }
}
