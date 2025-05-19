<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Messenger\DomainNameSpaceUsageValidator;

use Doctrine\Common\Collections\Criteria;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\Webhosting\Email\Forward;
use ParkManager\Domain\Webhosting\Email\ForwardRepository;
use ParkManager\Domain\Webhosting\Email\Mailbox;
use ParkManager\Domain\Webhosting\Email\MailboxRepository;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Infrastructure\Messenger\DomainNameSpaceUsageValidator;

final class EmailAddressDomainNameUsageValidator implements DomainNameSpaceUsageValidator
{
    public function __construct(
        private MailboxRepository $mailboxRepository,
        private ForwardRepository $forwardRepository
    ) {
    }

    public function __invoke(DomainName $domainName, Space $space): array
    {
        return [
            Mailbox::class => $this->mailboxRepository->allBySpace($space->id)
                ->filter(Criteria::expr()->eq('domainName', $domainName))
                ->setLimit(20),
            Forward::class => $this->forwardRepository->allBySpace($space->id)
                ->filter(Criteria::expr()->eq('domainName', $domainName))
                ->setLimit(20),
        ];
    }
}
