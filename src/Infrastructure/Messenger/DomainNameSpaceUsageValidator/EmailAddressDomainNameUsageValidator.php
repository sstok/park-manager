<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Messenger\DomainNameSpaceUsageValidator;

use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\Webhosting\Email\Forward;
use ParkManager\Domain\Webhosting\Email\ForwardRepository;
use ParkManager\Domain\Webhosting\Email\Mailbox;
use ParkManager\Domain\Webhosting\Email\MailboxRepository;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Infrastructure\Messenger\DomainNameSpaceUsageValidator;

final class EmailAddressDomainNameUsageValidator implements DomainNameSpaceUsageValidator
{
    private MailboxRepository $mailboxRepository;
    private ForwardRepository $forwardRepository;

    public function __construct(MailboxRepository $mailboxRepository, ForwardRepository $forwardRepository)
    {
        $this->mailboxRepository = $mailboxRepository;
        $this->forwardRepository = $forwardRepository;
    }

    public function __invoke(DomainName $domainName, Space $space): array
    {
        $entities = [
            Mailbox::class => [],
            Forward::class => [],
        ];

        foreach ($this->mailboxRepository->allBySpace($space->id) as $mailbox) {
            if ($mailbox->domainName === $domainName) {
                $entities[Mailbox::class][] = $mailbox;
            }
        }

        foreach ($this->forwardRepository->allBySpace($space->id) as $forward) {
            if ($forward->domainName === $domainName) {
                $entities[Forward::class][] = $forward;
            }
        }

        return $entities;
    }
}
