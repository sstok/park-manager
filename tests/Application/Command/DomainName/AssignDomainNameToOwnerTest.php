<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\DomainName;

use ParkManager\Application\Command\DomainName\AssignDomainNameToOwner;
use ParkManager\Application\Command\DomainName\AssignDomainNameToOwnerHandler;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\Owner;
use ParkManager\Domain\OwnerId;
use ParkManager\Tests\Mock\Domain\DomainName\DomainNameRepositoryMock;
use ParkManager\Tests\Mock\Domain\OwnerRepositoryMock;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class AssignDomainNameToOwnerTest extends TestCase
{
    private const USER_ID_1 = 'b2d70be9-31ff-4ceb-8305-e157acdca94f';
    private const USER_ID_2 = '2350ef87-877e-46c8-9c10-d199a9b16980';

    private const DOMAIN_ID_1 = 'ab53f769-cadc-4e7f-8f6d-e2e5a1ef5494';
    private const DOMAIN_ID_2 = '4f459680-0673-4e5a-940c-fc0cd5bd052c';

    private DomainNameRepositoryMock $domainNameRepository;
    private AssignDomainNameToOwnerHandler $handler;

    protected function setUp(): void
    {
        $userRepository = new OwnerRepositoryMock([
            $user1 = Owner::byUser(UserRepositoryMock::createUser('bella.richards@example.com', self::USER_ID_1)),
            $user2 = Owner::byUser(UserRepositoryMock::createUser('johnni.dunn@example.net', self::USER_ID_2)),
        ]);

        $this->domainNameRepository = new DomainNameRepositoryMock([
            DomainName::register(
                DomainNameId::fromString(self::DOMAIN_ID_1),
                new DomainNamePair('example', 'com'),
                $user1,
            ),
            DomainName::register(
                DomainNameId::fromString(self::DOMAIN_ID_2),
                new DomainNamePair('example', 'net'),
                $user2,
            ),
        ]);

        $this->handler = new AssignDomainNameToOwnerHandler($this->domainNameRepository, $userRepository);
    }

    /** @test */
    public function transfer_to_owner_as_primary(): void
    {
        $this->handler->__invoke(AssignDomainNameToOwner::with(self::DOMAIN_ID_2, self::USER_ID_1));

        $this->domainNameRepository->assertEntitiesCountWasSaved(1);
        $this->domainNameRepository->assertEntityWasSavedThat(
            self::DOMAIN_ID_2,
            static fn (DomainName $domainName) => $domainName->space === null && OwnerId::equalsValueOfEntity(OwnerId::fromString(self::USER_ID_1), $domainName->owner, 'id')
        );
    }
}
