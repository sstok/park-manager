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
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\ConstraintSetId;
use ParkManager\Domain\Webhosting\Constraint\SharedConstraintSet;
use ParkManager\Domain\Webhosting\DomainName;
use ParkManager\Domain\Webhosting\DomainName\Exception\DomainNameAlreadyInUse;
use ParkManager\Domain\Webhosting\DomainName\WebhostingDomainName;
use ParkManager\Domain\Webhosting\DomainName\WebhostingDomainNameId;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceId;
use ParkManager\Tests\Infrastructure\Webhosting\Fixtures\MonthlyTrafficQuota;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SharedConstraintSetRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\WebhostingDomainNameRepositoryMock;
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
        $domainName = new DomainName('example', '.com');
        $constraintSet = new SharedConstraintSet(ConstraintSetId::fromString(self::SET_ID1), new Constraints(new MonthlyTrafficQuota(50)));

        $spaceRepository = new SpaceRepositoryMock();
        $constraintSetRepository = new SharedConstraintSetRepositoryMock([$constraintSet]);
        $domainNameRepository = new WebhostingDomainNameRepositoryMock();
        $userRepository = new UserRepositoryMock([$user = UserRepositoryMock::createUser()]);
        $handler = new RegisterWebhostingSpaceHandler($spaceRepository, $constraintSetRepository, $domainNameRepository, $userRepository);

        $handler(RegisterWebhostingSpace::withConstraintSet(self::SPACE_ID1, $domainName, self::USER_ID1, self::SET_ID1));

        $spaceRepository->assertEntitiesWereSaved([
            Space::register(WebhostingSpaceId::fromString(self::SPACE_ID1), $user, $constraintSet),
        ]);

        $domainNameRepository->assertEntitiesCountWasSaved(1);
        $domainNameRepository->assertHasEntityThat(static function (WebhostingDomainName $storedDomainName) use ($domainName) {
            if ($domainName->toString() !== $storedDomainName->getDomainName()->toString()) {
                return false;
            }

            if (! $storedDomainName->isPrimary()) {
                return false;
            }

            if (! $storedDomainName->getSpace()->getId()->equals(WebhostingSpaceId::fromString(self::SPACE_ID1))) {
                return false;
            }

            return true;
        });
    }

    /** @test */
    public function it_handles_registration_of_space_with_custom_constraints(): void
    {
        $domainName = new DomainName('example', '.com');
        $constraints = new Constraints(new MonthlyTrafficQuota(50));

        $spaceRepository = new SpaceRepositoryMock();
        $constraintSetRepository = new SharedConstraintSetRepositoryMock();
        $domainNameRepository = new WebhostingDomainNameRepositoryMock();
        $userRepository = new UserRepositoryMock([$user = UserRepositoryMock::createUser()]);
        $handler = new RegisterWebhostingSpaceHandler($spaceRepository, $constraintSetRepository, $domainNameRepository, $userRepository);

        $handler(RegisterWebhostingSpace::withCustomConstraints(self::SPACE_ID1, $domainName, self::USER_ID1, $constraints));

        $spaceRepository->assertEntitiesWereSaved([
            Space::registerWithCustomConstraints(WebhostingSpaceId::fromString(self::SPACE_ID1), $user, $constraints),
        ]);

        $domainNameRepository->assertEntitiesCountWasSaved(1);
        $domainNameRepository->assertHasEntityThat(static function (WebhostingDomainName $storedDomainName) use ($domainName) {
            if ($domainName->toString() !== $storedDomainName->getDomainName()->toString()) {
                return false;
            }

            if (! $storedDomainName->isPrimary()) {
                return false;
            }

            if (! $storedDomainName->getSpace()->getId()->equals(WebhostingSpaceId::fromString(self::SPACE_ID1))) {
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
        $domainNameRepository = new WebhostingDomainNameRepositoryMock([$this->createExistingDomain($domainName = new DomainName('example', '.com'), $spaceId2 = WebhostingSpaceId::fromString(self::SPACE_ID2))]);
        $handler = new RegisterWebhostingSpaceHandler($spaceRepository, $constraintSetRepository, $domainNameRepository, new UserRepositoryMock([UserRepositoryMock::createUser()]));

        try {
            $handler(RegisterWebhostingSpace::withConstraintSet(self::SPACE_ID1, $domainName, UserRepositoryMock::USER_ID1, self::SET_ID1));

            self::fail('Exception to be thrown.');
        } catch (DomainNameAlreadyInUse $e) {
            self::assertEquals(DomainNameAlreadyInUse::bySpaceId($domainName, $spaceId2), $e);
        }

        $spaceRepository->assertNoEntitiesWereSaved();
        $domainNameRepository->assertNoEntitiesWereSaved();
    }

    private function createExistingDomain(DomainName $domainName, WebhostingSpaceId $existingSpaceId): WebhostingDomainName
    {
        $existingSpace = Space::registerWithCustomConstraints($existingSpaceId, UserRepositoryMock::createUser(), new Constraints(new MonthlyTrafficQuota(50)));

        $existingDomain = $this->prophesize(WebhostingDomainName::class);
        $existingDomain->getId()->willReturn(WebhostingDomainNameId::fromString('10abb1db-6e93-4dfc-9ba1-cdd46a225657'));
        $existingDomain->getSpace()->willReturn($existingSpace);
        $existingDomain->getDomainName()->willReturn($domainName);
        $existingDomain->isPrimary()->willReturn(false);

        return $existingDomain->reveal();
    }
}
