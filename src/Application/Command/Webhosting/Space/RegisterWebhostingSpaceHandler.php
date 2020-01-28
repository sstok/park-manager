<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Space;

use ParkManager\Domain\Webhosting\Constraint\SharedConstraintSetRepository;
use ParkManager\Domain\Webhosting\DomainName\Exception\DomainNameAlreadyInUse;
use ParkManager\Domain\Webhosting\DomainName\WebhostingDomainName;
use ParkManager\Domain\Webhosting\DomainName\WebhostingDomainNameRepository;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceRepository;

final class RegisterWebhostingSpaceHandler
{
    private $spaceRepository;
    private $constraintSetRepository;
    private $domainNameRepository;

    public function __construct(WebhostingSpaceRepository $spaceRepository, SharedConstraintSetRepository $constraintSetRepository, WebhostingDomainNameRepository $domainNameRepository)
    {
        $this->spaceRepository = $spaceRepository;
        $this->constraintSetRepository = $constraintSetRepository;
        $this->domainNameRepository = $domainNameRepository;
    }

    public function __invoke(RegisterWebhostingSpace $command): void
    {
        $currentRegistration = $this->domainNameRepository->findByFullName($command->domainName);

        $constraintSetId = $command->constraintSetId;

        if ($currentRegistration !== null) {
            throw DomainNameAlreadyInUse::bySpaceId($command->domainName, $currentRegistration->getSpace()->getId());
        }

        if ($constraintSetId !== null) {
            $space = Space::register(
                $command->id,
                $command->owner,
                $this->constraintSetRepository->get($constraintSetId)
            );
        } else {
            $space = Space::registerWithCustomConstraints(
                $command->id,
                $command->owner,
                $command->customConstraints
            );
        }

        $primaryDomainName = WebhostingDomainName::registerPrimary($space, $command->domainName);

        $this->spaceRepository->save($space);
        $this->domainNameRepository->save($primaryDomainName);
    }
}