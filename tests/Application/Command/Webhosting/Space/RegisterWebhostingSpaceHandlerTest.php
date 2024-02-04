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
use ParkManager\Application\Command\Webhosting\Space\RegisterWebhostingSpace;
use ParkManager\Application\Command\Webhosting\Space\RegisterWebhostingSpaceHandler;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\Exception\DomainNameAlreadyInUse;
use ParkManager\Domain\Owner;
use ParkManager\Domain\OwnerId;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Mock\Application\Service\SpyingMessageBus;
use ParkManager\Tests\Mock\Domain\DomainName\DomainNameRepositoryMock;
use ParkManager\Tests\Mock\Domain\OwnerRepositoryMock;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\PlanRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RegisterWebhostingSpaceHandlerTest extends TestCase
{
    private const USER_ID1 = UserRepositoryMock::USER_ID1;
    private const SET_ID1 = '2570c850-a5e0-11e7-868d-acbc32b58315';

    private const SPACE_ID1 = '2d3fb900-a528-11e7-a027-acbc32b58315';
    private const SPACE_ID2 = '696d345c-a5e1-11e7-9856-acbc32b58315';

    private OwnerRepositoryMock $ownerRepository;

    protected function setUp(): void
    {
        $this->ownerRepository = new OwnerRepositoryMock([
            Owner::byUser(UserRepositoryMock::createUser(id: UserRepositoryMock::USER_ID1)),
        ]);
    }

    /** @test */
    public function it_handles_registration_of_space_with_shared_constraints(): void
    {
        $domainName = new DomainNamePair('example', 'com');
        $plan = new Plan(PlanId::fromString(self::SET_ID1), new Constraints());

        $spaceRepository = new SpaceRepositoryMock();
        $planRepository = new PlanRepositoryMock([$plan]);
        $domainNameRepository = new DomainNameRepositoryMock();
        $messageBus = new SpyingMessageBus();

        $handler = new RegisterWebhostingSpaceHandler($spaceRepository, $planRepository, $domainNameRepository, $this->ownerRepository, $messageBus);

        $handler(RegisterWebhostingSpace::withPlan(self::SPACE_ID1, $domainName, self::USER_ID1, self::SET_ID1));

        $space = Space::register(SpaceId::fromString(self::SPACE_ID1), $this->getUserOwner(), $plan);
        $space->setPrimaryDomainLabel($domainName);

        $spaceRepository->assertEntitiesWereSaved([$space]);

        $domainNameRepository->assertEntitiesCountWasSaved(1);
        $domainNameRepository->assertHasEntityThat(static function (DomainName $storedDomainName) use ($domainName) {
            if ($domainName->toString() !== $storedDomainName->namePair->toString()) {
                return false;
            }

            if (! $storedDomainName->isPrimary()) {
                return false;
            }

            return $storedDomainName->space->id->equals(SpaceId::fromString(self::SPACE_ID1));
        });

        self::assertEquals([new InitializeWebhostingSpace(SpaceId::fromString(self::SPACE_ID1))], $messageBus->dispatchedMessages);
    }

    /** @test */
    public function it_handles_registration_of_space_with_custom_constraints(): void
    {
        $domainName = new DomainNamePair('example', 'com');
        $constraints = new Constraints();

        $spaceRepository = new SpaceRepositoryMock();
        $planRepository = new PlanRepositoryMock();
        $domainNameRepository = new DomainNameRepositoryMock();
        $messageBus = new SpyingMessageBus();

        $handler = new RegisterWebhostingSpaceHandler($spaceRepository, $planRepository, $domainNameRepository, $this->ownerRepository, $messageBus);

        $handler(RegisterWebhostingSpace::withCustomConstraints(self::SPACE_ID1, $domainName, UserRepositoryMock::USER_ID1, $constraints));

        $space = Space::registerWithCustomConstraints(SpaceId::fromString(self::SPACE_ID1), $this->getUserOwner(), $constraints);
        $space->setPrimaryDomainLabel($domainName);

        $spaceRepository->assertEntitiesWereSaved([$space]);

        $domainNameRepository->assertEntitiesCountWasSaved(1);
        $domainNameRepository->assertHasEntityThat(static function (DomainName $storedDomainName) use ($domainName) {
            if ($domainName->toString() !== $storedDomainName->namePair->toString()) {
                return false;
            }

            if (! $storedDomainName->isPrimary()) {
                return false;
            }

            return $storedDomainName->space->id->equals(SpaceId::fromString(self::SPACE_ID1));
        });

        self::assertEquals([new InitializeWebhostingSpace(SpaceId::fromString(self::SPACE_ID1))], $messageBus->dispatchedMessages);
    }

    /** @test */
    public function it_checks_domain_is_not_already_registered(): void
    {
        $plan = new Plan(PlanId::fromString(self::SET_ID1), new Constraints());
        $planRepository = new PlanRepositoryMock([$plan]);
        $spaceRepository = new SpaceRepositoryMock();
        $domainNameRepository = new DomainNameRepositoryMock([$this->createExistingDomain($domainName = new DomainNamePair('example', '.com'), SpaceId::fromString(self::SPACE_ID2))]);
        $messageBus = new SpyingMessageBus();

        $handler = new RegisterWebhostingSpaceHandler($spaceRepository, $planRepository, $domainNameRepository, $this->ownerRepository, $messageBus);

        try {
            $handler(RegisterWebhostingSpace::withPlan(self::SPACE_ID1, $domainName, UserRepositoryMock::USER_ID1, self::SET_ID1));

            self::fail('Exception should be thrown.');
        } catch (DomainNameAlreadyInUse $e) {
            self::assertEquals(new DomainNameAlreadyInUse($domainName), $e);
        }

        // While technically no entities should be saved, the Space must be 'persisted'
        // before the DomainName to prevent untracked entities in the UnitOfWork.
        $spaceRepository->assertEntitiesCountWasSaved(1);
        $domainNameRepository->assertNoEntitiesWereSaved();

        self::assertSame([], $messageBus->dispatchedMessages);
    }

    private function createExistingDomain(DomainNamePair $domainName, SpaceId $existingSpaceId): DomainName
    {
        $space = Space::registerWithCustomConstraints(
            id: $existingSpaceId,
            owner: $this->ownerRepository->getAdminOrganization(),
            constraints: new Constraints()
        );

        return DomainName::registerSecondaryForSpace(DomainNameId::fromString('10abb1db-6e93-4dfc-9ba1-cdd46a225657'), $space, $domainName);
    }

    /** @test */
    public function it_assigns_existing_single_domain_name_with_same_owner(): void
    {
        $domainName = new DomainNamePair('example', '.com');
        $constraints = new Constraints();

        $spaceRepository = new SpaceRepositoryMock();
        $planRepository = new PlanRepositoryMock();
        $domainNameRepository = new DomainNameRepositoryMock([$this->createExistingDomainWithOwner($domainName, $this->getUserOwner())]);
        $messageBus = new SpyingMessageBus();

        $handler = new RegisterWebhostingSpaceHandler($spaceRepository, $planRepository, $domainNameRepository, $this->ownerRepository, $messageBus);

        $handler(RegisterWebhostingSpace::withCustomConstraints(self::SPACE_ID1, $domainName, self::USER_ID1, $constraints));

        $space = Space::registerWithCustomConstraints(SpaceId::fromString(self::SPACE_ID1), $this->getUserOwner(), $constraints);
        $space->setPrimaryDomainLabel($domainName);

        $spaceRepository->assertEntitiesWereSaved([$space]);

        $domainNameRepository->assertEntitiesCountWasSaved(1);
        $domainNameRepository->assertHasEntityThat(static function (DomainName $storedDomainName) use ($domainName) {
            if ($domainName->toString() !== $storedDomainName->namePair->toString()) {
                return false;
            }

            if (! $storedDomainName->isPrimary()) {
                return false;
            }

            return $storedDomainName->space->id->equals(SpaceId::fromString(self::SPACE_ID1));
        });

        self::assertEquals([new InitializeWebhostingSpace(SpaceId::fromString(self::SPACE_ID1))], $messageBus->dispatchedMessages);
    }

    private function createExistingDomainWithOwner(DomainNamePair $domainName, Owner $owner): DomainName
    {
        return DomainName::register(DomainNameId::fromString('10abb1db-6e93-4dfc-9ba1-cdd46a225657'), $domainName, $owner);
    }

    private function getUserOwner(): Owner
    {
        return $this->ownerRepository->get(OwnerId::fromString(UserRepositoryMock::USER_ID1));
    }
}
