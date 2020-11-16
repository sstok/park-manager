<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\DomainName;

use ParkManager\Application\Command\DomainName\AssignDomainNameToSpace;
use ParkManager\Application\Command\DomainName\AssignDomainNameToSpaceHandler;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Mock\Domain\DomainName\DomainNameRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class AssignDomainNameToSpaceHandlerTest extends TestCase
{
    private const SPACE_ID_1 = '09d3bcc6-d155-47f4-b619-9352d38efe90';

    private const DOMAIN_ID_1 = 'ab53f769-cadc-4e7f-8f6d-e2e5a1ef5494';
    private const DOMAIN_ID_2 = '4f459680-0673-4e5a-940c-fc0cd5bd052c';

    private DomainNameRepositoryMock $domainNameRepository;
    private AssignDomainNameToSpaceHandler $handler;

    protected function setUp(): void
    {
        $space1 = SpaceRepositoryMock::createSpace(self::SPACE_ID_1);
        $space2 = SpaceRepositoryMock::createSpace('576bf8d3-69eb-4b6e-9e54-96d906809192');

        $spaceRepository = new SpaceRepositoryMock([$space1, $space2]);
        $this->domainNameRepository = new DomainNameRepositoryMock([
            DomainName::registerForSpace(
                DomainNameId::fromString(self::DOMAIN_ID_1),
                $space1,
                new DomainNamePair('example', 'com')
            ),
            DomainName::registerSecondaryForSpace(
                DomainNameId::fromString(self::DOMAIN_ID_2),
                $space2,
                new DomainNamePair('example', 'net')
            ),
        ]);

        $this->handler = new AssignDomainNameToSpaceHandler($this->domainNameRepository, $spaceRepository);
    }

    /** @test */
    public function transfer_to_space_as_primary(): void
    {
        $this->handler->__invoke(AssignDomainNameToSpace::with(self::DOMAIN_ID_2, self::SPACE_ID_1, true));

        $this->domainNameRepository->assertEntitiesCountWasSaved(2);
        $this->domainNameRepository->assertEntityWasSavedThat(
            self::DOMAIN_ID_1,
            static fn (DomainName $domainName) => $domainName->space->id->equals(SpaceId::fromString(self::SPACE_ID_1)) && ! $domainName->isPrimary()
        );
        $this->domainNameRepository->assertEntityWasSavedThat(
            self::DOMAIN_ID_2,
            static fn (DomainName $domainName) => $domainName->space->id->equals(SpaceId::fromString(self::SPACE_ID_1)) && $domainName->isPrimary()
        );
    }

    /** @test */
    public function transfer_to_space_as_secondary(): void
    {
        $this->handler->__invoke(AssignDomainNameToSpace::with(self::DOMAIN_ID_2, self::SPACE_ID_1, false));

        $this->domainNameRepository->assertEntitiesCountWasSaved(1);
        $this->domainNameRepository->assertEntityWasSavedThat(
            self::DOMAIN_ID_2,
            static fn (DomainName $domainName) => $domainName->space->id->equals(SpaceId::fromString(self::SPACE_ID_1)) && ! $domainName->isPrimary()
        );
    }
}
