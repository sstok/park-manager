<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\Space;

use ParkManager\Application\Command\Webhosting\Space\RegisterWebhostingSpace;
use ParkManager\Application\Command\Webhosting\Space\RegisterWebhostingSpaceHandler;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\DomainName\Exception\DomainNameAlreadyInUse;
use ParkManager\Domain\User\User;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\ConstraintSetId;
use ParkManager\Domain\Webhosting\Constraint\SharedConstraintSet;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Infrastructure\Webhosting\Fixtures\MonthlyTrafficQuota;
use ParkManager\Tests\Mock\Domain\DomainName\DomainNameRepositoryMock;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SharedConstraintSetRepositoryMock;
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

    /** @test */
    public function it_handles_registration_of_space_with_shared_constraints(): void
    {
        $domainName = new DomainNamePair('example', '.com');
        $constraintSet = new SharedConstraintSet(ConstraintSetId::fromString(self::SET_ID1), new Constraints(new MonthlyTrafficQuota(50)));

        $spaceRepository = new SpaceRepositoryMock();
        $constraintSetRepository = new SharedConstraintSetRepositoryMock([$constraintSet]);
        $domainNameRepository = new DomainNameRepositoryMock();
        $userRepository = new UserRepositoryMock([$user = UserRepositoryMock::createUser()]);
        $handler = new RegisterWebhostingSpaceHandler($spaceRepository, $constraintSetRepository, $domainNameRepository, $userRepository);

        $handler(RegisterWebhostingSpace::withConstraintSet(self::SPACE_ID1, $domainName, self::USER_ID1, self::SET_ID1));

        $spaceRepository->assertEntitiesWereSaved([
            Space::register(SpaceId::fromString(self::SPACE_ID1), $user, $constraintSet),
        ]);

        $domainNameRepository->assertEntitiesCountWasSaved(1);
        $domainNameRepository->assertHasEntityThat(static function (DomainName $storedDomainName) use ($domainName) {
            if ($domainName->toString() !== $storedDomainName->getNamePair()->toString()) {
                return false;
            }

            if (! $storedDomainName->isPrimary()) {
                return false;
            }

            if (! $storedDomainName->getSpace()->getId()->equals(SpaceId::fromString(self::SPACE_ID1))) {
                return false;
            }

            return true;
        });
    }

    /** @test */
    public function it_handles_registration_of_space_with_custom_constraints(): void
    {
        $domainName = new DomainNamePair('example', '.com');
        $constraints = new Constraints(new MonthlyTrafficQuota(50));

        $spaceRepository = new SpaceRepositoryMock();
        $constraintSetRepository = new SharedConstraintSetRepositoryMock();
        $domainNameRepository = new DomainNameRepositoryMock();
        $userRepository = new UserRepositoryMock([$user = UserRepositoryMock::createUser()]);
        $handler = new RegisterWebhostingSpaceHandler($spaceRepository, $constraintSetRepository, $domainNameRepository, $userRepository);

        $handler(RegisterWebhostingSpace::withCustomConstraints(self::SPACE_ID1, $domainName, self::USER_ID1, $constraints));

        $spaceRepository->assertEntitiesWereSaved([
            Space::registerWithCustomConstraints(SpaceId::fromString(self::SPACE_ID1), $user, $constraints),
        ]);

        $domainNameRepository->assertEntitiesCountWasSaved(1);
        $domainNameRepository->assertHasEntityThat(static function (DomainName $storedDomainName) use ($domainName) {
            if ($domainName->toString() !== $storedDomainName->getNamePair()->toString()) {
                return false;
            }

            if (! $storedDomainName->isPrimary()) {
                return false;
            }

            if (! $storedDomainName->getSpace()->getId()->equals(SpaceId::fromString(self::SPACE_ID1))) {
                return false;
            }

            return true;
        });
    }

    /** @test */
    public function it_checks_domain_is_not_already_registered(): void
    {
        $constraintSet = new SharedConstraintSet(ConstraintSetId::fromString(self::SET_ID1), new Constraints(new MonthlyTrafficQuota(50)));
        $constraintSetRepository = new SharedConstraintSetRepositoryMock([$constraintSet]);
        $spaceRepository = new SpaceRepositoryMock();
        $domainNameRepository = new DomainNameRepositoryMock([$this->createExistingDomain($domainName = new DomainNamePair('example', '.com'), $spaceId2 = SpaceId::fromString(self::SPACE_ID2))]);
        $handler = new RegisterWebhostingSpaceHandler($spaceRepository, $constraintSetRepository, $domainNameRepository, new UserRepositoryMock([UserRepositoryMock::createUser()]));

        try {
            $handler(RegisterWebhostingSpace::withConstraintSet(self::SPACE_ID1, $domainName, UserRepositoryMock::USER_ID1, self::SET_ID1));

            self::fail('Exception should be thrown.');
        } catch (DomainNameAlreadyInUse $e) {
            self::assertEquals(new DomainNameAlreadyInUse($domainName), $e);
        }

        $spaceRepository->assertNoEntitiesWereSaved();
        $domainNameRepository->assertNoEntitiesWereSaved();
    }

    private function createExistingDomain(DomainNamePair $domainName, SpaceId $existingSpaceId): DomainName
    {
        $space = Space::registerWithCustomConstraints($existingSpaceId, UserRepositoryMock::createUser(), new Constraints(new MonthlyTrafficQuota(50)));

        return DomainName::registerSecondaryForSpace(DomainNameId::fromString('10abb1db-6e93-4dfc-9ba1-cdd46a225657'), $space, $domainName);
    }

    /** @test */
    public function it_assigns_existing_single_domain_name_with_same_owner(): void
    {
        $domainName = new DomainNamePair('example', '.com');
        $constraints = new Constraints(new MonthlyTrafficQuota(50));

        $spaceRepository = new SpaceRepositoryMock();
        $constraintSetRepository = new SharedConstraintSetRepositoryMock();
        $userRepository = new UserRepositoryMock([$user = UserRepositoryMock::createUser()]);
        $domainNameRepository = new DomainNameRepositoryMock([$this->createExistingDomainWithOwner($domainName, $user)]);
        $handler = new RegisterWebhostingSpaceHandler($spaceRepository, $constraintSetRepository, $domainNameRepository, $userRepository);

        $handler(RegisterWebhostingSpace::withCustomConstraints(self::SPACE_ID1, $domainName, self::USER_ID1, $constraints));

        $spaceRepository->assertEntitiesWereSaved([
            Space::registerWithCustomConstraints(SpaceId::fromString(self::SPACE_ID1), $user, $constraints),
        ]);

        $domainNameRepository->assertEntitiesCountWasSaved(1);
        $domainNameRepository->assertHasEntityThat(static function (DomainName $storedDomainName) use ($domainName) {
            if ($domainName->toString() !== $storedDomainName->getNamePair()->toString()) {
                return false;
            }

            if (! $storedDomainName->isPrimary()) {
                return false;
            }

            if (! $storedDomainName->getSpace()->getId()->equals(SpaceId::fromString(self::SPACE_ID1))) {
                return false;
            }

            return true;
        });
    }

    private function createExistingDomainWithOwner(DomainNamePair $domainName, ?User $user): DomainName
    {
        return DomainName::register(DomainNameId::fromString('10abb1db-6e93-4dfc-9ba1-cdd46a225657'), $domainName, $user);
    }
}
