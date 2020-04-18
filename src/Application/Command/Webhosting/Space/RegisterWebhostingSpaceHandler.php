<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Space;

use ParkManager\Domain\User\UserRepository;
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

    /** @var UserRepository */
    private $userRepository;

    public function __construct(WebhostingSpaceRepository $spaceRepository, SharedConstraintSetRepository $constraintSetRepository, WebhostingDomainNameRepository $domainNameRepository, UserRepository $userRepository)
    {
        $this->spaceRepository = $spaceRepository;
        $this->constraintSetRepository = $constraintSetRepository;
        $this->domainNameRepository = $domainNameRepository;
        $this->userRepository = $userRepository;
    }

    public function __invoke(RegisterWebhostingSpace $command): void
    {
        $owner = $command->owner === null ? null : $this->userRepository->get($command->owner);

        $currentRegistration = $this->domainNameRepository->findByFullName($command->domainName);

        if ($currentRegistration !== null) {
            throw DomainNameAlreadyInUse::bySpaceId($command->domainName, $currentRegistration->getSpace()->getId());
        }

        /** @psalm-suppress PossiblyNullOperand */
        if ($command->constraintSetId !== null) {
            $space = Space::register(
                $command->id,
                $owner,
                $this->constraintSetRepository->get($command->constraintSetId)
            );
        } else {
            $space = Space::registerWithCustomConstraints(
                $command->id,
                $owner,
                $command->customConstraints
            );
        }

        $primaryDomainName = WebhostingDomainName::registerPrimary($space, $command->domainName);

        $this->spaceRepository->save($space);
        $this->domainNameRepository->save($primaryDomainName);
    }
}
