<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Email\Mailbox;

use ParkManager\Application\Service\PasswordHasher;
use ParkManager\Application\Service\SpaceConstraint\ConstraintsChecker;
use ParkManager\Application\Service\SystemGateway;
use ParkManager\Application\Service\SystemGateway\Webhosting\CreateMailbox;
use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\Webhosting\Constraint\Exception\ConstraintExceeded;
use ParkManager\Domain\Webhosting\Email\Exception\AddressAlreadyExists;
use ParkManager\Domain\Webhosting\Email\ForwardRepository;
use ParkManager\Domain\Webhosting\Email\Mailbox;
use ParkManager\Domain\Webhosting\Email\MailboxRepository;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;

final class AddMailboxHandler
{
    public function __construct(
        private MailboxRepository $mailboxRepository,
        private ForwardRepository $forwardRepository,
        private SpaceRepository $spaceRepository,
        private DomainNameRepository $domainNameRepository,
        private ConstraintsChecker $constraintsChecker,
        private PasswordHasher $passwordHasher,
        private SystemGateway $systemGateway,
    ) {
    }

    /**
     * @throws ConstraintExceeded
     */
    public function __invoke(AddMailbox $command): void
    {
        $domainName = $this->domainNameRepository->get($command->domainName);

        $this->constraintsChecker->allowNewMailboxes(
            $command->space,
            [sprintf('%s@%s', $command->address, $domainName->toString()) => $command->size]
        );

        if ($this->forwardRepository->hasName($command->address, $domainName->namePair)) {
            throw new AddressAlreadyExists($command->address, $domainName->namePair);
        }

        $space = $this->spaceRepository->get($command->space);
        $password = $this->passwordHasher->hash($command->password);

        $mailbox = new Mailbox($command->id, $space, $command->address, $domainName, $command->size, $password);

        $this->systemGateway->execute(new CreateMailbox($command->id));
        $this->mailboxRepository->save($mailbox);
    }
}
