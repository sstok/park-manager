<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Messenger;

use ParkManager\Application\Command\DomainName\AssignDomainNameToOwner;
use ParkManager\Application\Command\DomainName\AssignDomainNameToSpace;
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

        if ($message instanceof AssignDomainNameToSpace || $message instanceof AssignDomainNameToOwner || $message instanceof RemoveDomainName) {
            $this->handleMessage($message);
        }

        return $stack->next()->handle($envelope, $stack);
    }

    /**
     * @param AssignDomainNameToOwner|AssignDomainNameToSpace|RemoveDomainName $message
     */
    private function handleMessage(object $message): void
    {
        $domainName = $this->domainNameRepository->get($message->id);
        $space = $domainName->space;

        if ($space === null) {
            return;
        }

        $usedByEntities = [];

        /** @var DomainNameSpaceUsageValidator $validator */
        foreach ($this->validators as $validator) {
            $usedByEntities[] = $validator($domainName, $space);
        }

        $usedByEntities = array_merge(...$usedByEntities);
        $usedByEntities = array_filter($usedByEntities, static fn ($value): bool => \count($value) > 0);

        if (\count($usedByEntities) === 0) {
            return;
        }

        if ($message instanceof RemoveDomainName) {
            throw new CannotRemoveInUseDomainName($domainName->namePair, $space->id, $usedByEntities);
        }

        throw new CannotTransferInUseDomainName($domainName->namePair, $space->id, $usedByEntities);
    }
}
