<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Messenger;

use ParkManager\Application\Command\DomainName\AssignDomainNameToSpace;
use ParkManager\Application\Command\DomainName\AssignDomainNameToUser;
use ParkManager\Application\Command\DomainName\RemoveDomainName;
use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\DomainName\Exception\CannotRemoveInUseDomainName;
use ParkManager\Domain\DomainName\Exception\CannotTransferInUseDomainName;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class DomainNameSpaceAssignmentValidator implements MiddlewareInterface
{
    private DomainNameRepository $domainNameRepository;

    /** @var iterable<DomainNameSpaceUsageValidator> */
    private iterable $validators;

    /**
     * @param iterable<DomainNameSpaceUsageValidator> $validators
     */
    public function __construct(DomainNameRepository $domainNameRepository, iterable $validators)
    {
        $this->domainNameRepository = $domainNameRepository;
        $this->validators = $validators;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();

        if ($message instanceof AssignDomainNameToSpace || $message instanceof AssignDomainNameToUser || $message instanceof RemoveDomainName) {
            $this->handleMessage($message);
        }

        return $stack->next()->handle($envelope, $stack);
    }

    /**
     * @param AssignDomainNameToSpace|AssignDomainNameToUser|RemoveDomainName $message
     */
    private function handleMessage(object $message): void
    {
        $domainName = $this->domainNameRepository->get($message->id);
        $space = $domainName->space;

        if ($space === null) {
            return;
        }

        try {
            /** @var DomainNameSpaceUsageValidator $validator */
            foreach ($this->validators as $validator) {
                $validator($domainName, $space);
            }
        } catch (CannotTransferInUseDomainName $e) {
            if ($message instanceof RemoveDomainName) {
                throw new CannotRemoveInUseDomainName($e->domainName, $e->current, $e->type, $e->id);
            }

            throw $e;
        }
    }
}
