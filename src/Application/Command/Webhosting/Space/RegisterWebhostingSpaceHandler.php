<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Space;

use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\DomainName\Exception\DomainNameAlreadyInUse;
use ParkManager\Domain\DomainName\Exception\DomainNameNotFound;
use ParkManager\Domain\OwnerRepository;
use ParkManager\Domain\Webhosting\Constraint\PlanRepository;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;
use Symfony\Component\Messenger\MessageBusInterface;

final class RegisterWebhostingSpaceHandler
{
    public function __construct(
        private SpaceRepository $spaceRepository,
        private PlanRepository $planRepository,
        private DomainNameRepository $domainNameRepository,
        private OwnerRepository $ownerRepository,
        private MessageBusInterface $commandBus
    ) {}

    public function __invoke(RegisterWebhostingSpace $command): void
    {
        $owner = $this->ownerRepository->get($command->owner);

        if ($command->planId !== null) {
            $space = Space::register(
                $command->id,
                $owner,
                $this->planRepository->get($command->planId)
            );
        } else {
            /** @psalm-suppress PossiblyNullOperand */
            $space = Space::registerWithCustomConstraints(
                $command->id,
                $owner,
                $command->customConstraints
            );
        }

        // Must be done before the domain-name assignment to ensure the Unit of work knows about this entity.
        $this->spaceRepository->save($space);

        try {
            $currentRegistration = $this->domainNameRepository->getByName($command->domainName);

            if ($currentRegistration->space !== null) {
                throw new DomainNameAlreadyInUse($command->domainName);
            }

            $currentRegistration->transferToSpace($space, true);
            $this->domainNameRepository->save($currentRegistration);
        } catch (DomainNameNotFound) {
            $primaryDomainName = DomainName::registerForSpace(DomainNameId::create(), $space, $command->domainName);
            $this->domainNameRepository->save($primaryDomainName);
        }

        // Once a Webhosting Space is registered it must initialized, registering the system user
        // and starting-up the server pool/cluster. As this is process is IO-blocking and might
        // take some time, it must be handled async in the background.
        $this->commandBus->dispatch(new InitializeWebhostingSpace($space->id));
    }
}
