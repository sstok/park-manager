<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Email\Forward;

use ParkManager\Application\Service\SpaceConstraint\ConstraintsChecker;
use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Webhosting\Email\Exception\AddressAlreadyExists;
use ParkManager\Domain\Webhosting\Email\Forward;
use ParkManager\Domain\Webhosting\Email\ForwardRepository;
use ParkManager\Domain\Webhosting\Email\MailboxRepository;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;

final class AddForwardHandler
{
    public function __construct(
        private ForwardRepository $forwardRepository,
        private SpaceRepository $spaceRepository,
        private DomainNameRepository $domainNameRepository,
        private MailboxRepository $mailboxRepository,
        private ConstraintsChecker $constraintsChecker,
    ) {
    }

    public function __invoke(AddForward $command): void
    {
        $domainName = $this->domainNameRepository->get($command->domainName);

        $this->constraintsChecker->allowNewEmailForward(
            $command->space,
            [sprintf('%s@%s', $command->address, $domainName->toString())]
        );

        if ($this->mailboxRepository->hasName($command->address, $domainName->namePair)) {
            throw new AddressAlreadyExists($command->address, $domainName->namePair);
        }

        $space = $this->spaceRepository->get($command->space);

        if ($command->destination instanceof EmailAddress) {
            $forward = Forward::toAddress($command->id, $space, $command->address, $domainName, $command->destination);
        } else {
            $forward = Forward::toScript($command->id, $space, $command->address, $domainName, $command->destination);
        }

        $this->forwardRepository->save($forward);
    }
}
