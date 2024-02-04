<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\Webhosting\Space;

use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParkManager\Application\Command\Webhosting\Space\InitializeWebhostingSpace;
use ParkManager\Application\Command\Webhosting\Space\InitializeWebhostingSpaceHandler;
use ParkManager\Application\Event\WebhostingSpaceFailedInitialization;
use ParkManager\Application\Event\WebhostingSpaceWasInitialized;
use ParkManager\Application\Service\SystemGateway;
use ParkManager\Application\Service\SystemGateway\OperationResult;
use ParkManager\Application\Service\SystemGateway\SystemCommand;
use ParkManager\Application\Service\SystemGateway\SystemQuery;
use ParkManager\Application\Service\SystemGateway\Webhosting\RegisterSystemUser;
use ParkManager\Application\Service\SystemGateway\Webhosting\RegisterSystemUserResult;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Domain\Webhosting\Space\SpaceSetupStatus;
use ParkManager\Domain\Webhosting\Space\SystemRegistration;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use ParkManager\Tests\Mock\SpyingEventDispatcher;
use ParkManager\Tests\TestLogger;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class InitializeWebhostingSpaceHandlerTest extends TestCase
{
    private const SPACE_ID2 = '5ac3d111-40db-4f2b-8f65-19bad3f1da9f';

    private SpaceRepositoryMock $spaceRepository;
    private SpyingEventDispatcher $eventDispatcher;
    private SystemGateway $systemGateway;
    private TestLogger $logger;
    private InitializeWebhostingSpaceHandler $handler;

    protected function setUp(): void
    {
        $this->spaceRepository = new SpaceRepositoryMock([
            SpaceRepositoryMock::createSpace(SpaceRepositoryMock::ID1),
            SpaceRepositoryMock::createSpace(self::SPACE_ID2, domainName: new DomainNamePair('example', 'net')),
        ]);
        $this->eventDispatcher = new SpyingEventDispatcher();
        $this->systemGateway = new class(self::SPACE_ID2) implements SystemGateway {
            private SpaceId $idForFailure;

            public function __construct(string $idForFailure)
            {
                $this->idForFailure = SpaceId::fromString($idForFailure);
            }

            public function execute(SystemCommand $command): OperationResult
            {
                if ($command instanceof RegisterSystemUser) {
                    if ($this->idForFailure->equals($command->getArguments()['space_id'])) {
                        throw new \InvalidArgumentException('The system is stretching its legs, I for one suggest you retry later.');
                    }

                    return new RegisterSystemUserResult(
                        ['id' => $id = 363, 'groups' => [500], 'homedir' => '/data/site_' . $id]
                    );
                }

                return throw new \InvalidArgumentException(
                    sprintf('Unsupported SystemCommand %s', $command::class)
                );
            }

            public function query(SystemQuery $command): OperationResult
            {
                throw new \InvalidArgumentException(sprintf('Unsupported SystemQuery %s', $command::class));
            }
        };

        $this->logger = new TestLogger();

        $this->handler = new InitializeWebhostingSpaceHandler(
            $this->spaceRepository,
            $this->eventDispatcher,
            $this->systemGateway,
            $this->logger
        );
    }

    /** @test */
    public function it_sets_up_space(): void
    {
        $this->spaceRepository->whenEntityIsSavedAt(
            SpaceRepositoryMock::ID1,
            static function (Space $space): void {
                self::assertSame(SpaceSetupStatus::GETTING_INITIALIZED, $space->setupStatus);
                self::assertNull($space->systemRegistration);
            },
            position: 1
        );
        $this->spaceRepository->whenEntityIsSavedAt(
            SpaceRepositoryMock::ID1,
            static function (Space $space): void {
                self::assertSame(SpaceSetupStatus::READY, $space->setupStatus);
                self::assertEquals(new SystemRegistration(363, [500], '/data/site_363'), $space->systemRegistration);
            },
            position: 2
        );

        ($this->handler)(new InitializeWebhostingSpace($id = SpaceId::fromString(SpaceRepositoryMock::ID1)));

        $this->spaceRepository->assertEntitiesCountWasSaved(2);
        $this->spaceRepository->assertEntityWasSavedThat(SpaceRepositoryMock::ID1, static fn (Space $space): bool => $space->setupStatus === SpaceSetupStatus::READY);

        self::assertEquals([new WebhostingSpaceWasInitialized($id)], $this->eventDispatcher->dispatchedEvents);
        self::assertEmpty($this->logger->records);
    }

    /** @test */
    public function it_handles_a_gateway_failure(): void
    {
        $this->spaceRepository->whenEntityIsSavedAt(
            self::SPACE_ID2,
            static function (Space $space): void {
                self::assertSame(SpaceSetupStatus::GETTING_INITIALIZED, $space->setupStatus);
                self::assertNull($space->systemRegistration);
            },
            position: 1
        );
        $this->spaceRepository->whenEntityIsSavedAt(
            self::SPACE_ID2,
            static function (Space $space): void {
                self::assertSame(SpaceSetupStatus::ERROR, $space->setupStatus);
                self::assertNull($space->systemRegistration);
            },
            position: 2
        );

        ($this->handler)(new InitializeWebhostingSpace($id = SpaceId::fromString(self::SPACE_ID2)));

        $this->spaceRepository->assertEntitiesCountWasSaved(2);
        $this->spaceRepository->assertEntityWasSavedThat(self::SPACE_ID2, static fn (Space $space): bool => $space->setupStatus === SpaceSetupStatus::ERROR);

        self::assertTrue(
            $this->logger->hasRecordThatPasses(
                static function (array $record) {
                    if ($record['message'] !== 'Failed to Initialize Webhosting Space "{space}" ({domain_name}).') {
                        return false;
                    }

                    if (! isset($record['context']['error'])) {
                        return false;
                    }

                    if (($record['context']['{space}'] ?? '') !== self::SPACE_ID2) {
                        return false;
                    }

                    if (($record['context']['{domain_name}'] ?? '') !== 'example.net') {
                        return false;
                    }

                    if (! $record['context']['error'] instanceof \InvalidArgumentException) {
                        return false;
                    }

                    return $record['context']['error']->getMessage() === 'The system is stretching its legs, I for one suggest you retry later.';
                },
                'error'
            )
        );

        self::assertEquals([new WebhostingSpaceFailedInitialization($id)], $this->eventDispatcher->dispatchedEvents);
    }

    /** @test */
    public function it_ignores_when_already_setup(): void
    {
        $space = $this->spaceRepository->get(SpaceId::fromString(self::SPACE_ID2));
        $space->assignSetupStatus(SpaceSetupStatus::GETTING_INITIALIZED);
        $space->setupWith(543, [500, 600], '/data/site_543');

        $this->spaceRepository->save($space);
        $this->spaceRepository->resetRecordingState();

        ($this->handler)(new InitializeWebhostingSpace(SpaceId::fromString(self::SPACE_ID2)));

        $this->spaceRepository->assertEntitiesCountWasSaved(0);
        self::assertSame([], $this->eventDispatcher->dispatchedEvents);
        self::assertEmpty($this->logger->records);
    }

    /** @test */
    public function it_ignores_when_current_status_is_error(): void
    {
        $space = $this->spaceRepository->get(SpaceId::fromString(self::SPACE_ID2));
        $space->assignSetupStatus(SpaceSetupStatus::ERROR);

        $this->spaceRepository->save($space);
        $this->spaceRepository->resetRecordingState();

        ($this->handler)(new InitializeWebhostingSpace(SpaceId::fromString(self::SPACE_ID2)));

        $this->spaceRepository->assertEntitiesCountWasSaved(0);
        self::assertSame([], $this->eventDispatcher->dispatchedEvents);
        self::assertEmpty($this->logger->records);
    }
}
