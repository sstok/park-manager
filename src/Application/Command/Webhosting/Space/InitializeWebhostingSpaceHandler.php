<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Space;

use ParkManager\Application\Event\WebhostingSpaceFailedInitialization;
use ParkManager\Application\Event\WebhostingSpaceWasInitialized;
use ParkManager\Application\Service\SystemGateway;
use ParkManager\Application\Service\SystemGateway\Webhosting\RegisterSystemUser;
use ParkManager\Application\Service\SystemGateway\Webhosting\RegisterSystemUserResult;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;
use ParkManager\Domain\Webhosting\Space\SpaceSetupStatus;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class InitializeWebhostingSpaceHandler
{
    public function __construct(
        private SpaceRepository $spaceRepository,
        private EventDispatcherInterface $eventDispatcher,
        private SystemGateway $systemGateway,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(InitializeWebhostingSpace $command): void
    {
        $space = $this->spaceRepository->get($command->space);

        // Ignore when set-up is already completed.
        if ($space->setupStatus === SpaceSetupStatus::READY) {
            return;
        }

        // Ignore when current status is error. This needs to be redispatched manually.
        if ($space->setupStatus === SpaceSetupStatus::ERROR) {
            return;
        }

        $space->assignSetupStatus(SpaceSetupStatus::GETTING_INITIALIZED);
        $this->spaceRepository->save($space);

        try {
            $result = $this->systemGateway->execute(new RegisterSystemUser($space->id));
            \assert($result instanceof RegisterSystemUserResult);

            $space->setupWith($result->userId(), $result->userGroups(), $result->homeDirectory());

            $this->spaceRepository->save($space);

            $this->eventDispatcher->dispatch(new WebhostingSpaceWasInitialized($space->id));
        } catch (Throwable $e) {
            $this->logger->error(
                'Failed to Initialize Webhosting Space "{space}" ({domain_name}).',
                [
                    '{space}' => $space->id->toString(),
                    '{domain_name}' => $space->primaryDomainLabel->toString(),
                    'error' => $e,
                ]
            );

            $this->eventDispatcher->dispatch(new WebhostingSpaceFailedInitialization($space->id));

            $space->assignSetupStatus(SpaceSetupStatus::ERROR);

            // Due note that there is still a possibility this will fail when the exception relates
            // to the UnitOfWork or when the Connection is closed.
            $this->spaceRepository->save($space);
        }
    }
}
