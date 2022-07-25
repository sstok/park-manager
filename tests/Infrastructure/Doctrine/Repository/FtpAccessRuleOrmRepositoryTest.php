<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Doctrine\Repository;

use IPLib\Factory as IPFactory;
use ParkManager\Domain\Webhosting\Ftp\AccessRule;
use ParkManager\Domain\Webhosting\Ftp\AccessRuleId;
use ParkManager\Domain\Webhosting\Ftp\AccessRuleStrategy;
use ParkManager\Domain\Webhosting\Ftp\FtpUser;
use ParkManager\Domain\Webhosting\Ftp\FtpUserId;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Infrastructure\Doctrine\Repository\FtpAccessRuleOrmRepository;
use ParkManager\Infrastructure\Doctrine\Repository\FtpUserOrmRepository;

/**
 * @internal
 *
 * @group functional
 */
final class FtpAccessRuleOrmRepositoryTest extends WebhostingRepositoryTestCase
{
    private const USER_ID1 = '84bdf8df-a347-44bf-96c9-b163a99cd925';
    private const USER_ID2 = '2f41666b-b670-4dab-9ffb-65c51dfd53df';

    private const RULE1_SPACE1 = 'e6819c3d-daba-4041-8d7e-310e9adb58a5';
    private const RULE2_SPACE1 = '03699354-15c7-4034-817e-72fe9fd98cf1';
    private const RULE3_SPACE2 = '1c836500-cf94-4ec8-ac04-4df80cb019bd';

    private const RULE4_USER1 = '017cffd5-8c62-c611-fa77-51df968cc060';
    private const RULE5_USER2 = '017d000b-9404-df9d-f689-fc9b155973a8';

    private FtpUserOrmRepository $userRepository;
    private FtpAccessRuleOrmRepository $ruleRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $space1 = $this->spaceRepository->get(SpaceId::fromString(self::SPACE_ID1));
        $space2 = $this->spaceRepository->get(SpaceId::fromString(self::SPACE_ID2));
        $domainSpace1 = $this->domainRepository->getPrimaryOf($space1->id);

        $em = $this->getEntityManager();
        $em->beginTransaction();

        $this->userRepository = new FtpUserOrmRepository($em);
        $this->userRepository->save(
            $user1 = new FtpUser(
                FtpUserId::fromString(self::USER_ID1),
                $space1,
                'user1',
                'ElHomoWasHere',
                $domainSpace1
            )
        );
        $this->userRepository->save(
            $user2 = new FtpUser(
                FtpUserId::fromString(self::USER_ID2),
                $space1,
                'user2.example.com',
                'Visaaaaa',
                $domainSpace1
            )
        );

        $this->ruleRepository = new FtpAccessRuleOrmRepository($em);

        // Space1
        $this->ruleRepository->save(
            AccessRule::createForSpace(
                AccessRuleId::fromString(self::RULE1_SPACE1),
                $space1,
                IPFactory::parseAddressString('200.100.100.5')
            )
        );
        $this->ruleRepository->save(
            AccessRule::createForSpace(
                AccessRuleId::fromString(self::RULE2_SPACE1),
                $space1,
                IPFactory::parseAddressString('200.100.100.10')
            )
        );

        // Space2
        $this->ruleRepository->save(
            AccessRule::createForSpace(
                AccessRuleId::fromString(self::RULE3_SPACE2),
                $space2,
                IPFactory::parseRangeString('127.0.0.1/24'),
                AccessRuleStrategy::ALLOW
            )
        );

        // User1
        $this->ruleRepository->save(
            AccessRule::createForUser(
                AccessRuleId::fromString(self::RULE4_USER1),
                $user1,
                IPFactory::parseRangeString('127.0.0.1/24')
            )
        );

        // User2
        $this->ruleRepository->save(
            AccessRule::createForUser(
                AccessRuleId::fromString(self::RULE5_USER2),
                $user2,
                IPFactory::parseAddressString('200.100.100.50'),
                AccessRuleStrategy::ALLOW
            )
        );

        $em->flush();
        $em->commit();
        $em->clear();
    }

    /** @test */
    public function it_gets_stored_rules(): void
    {
        $space1 = $this->spaceRepository->get(SpaceId::fromString(self::SPACE_ID1));
        $user1 = $this->userRepository->get(FtpUserId::fromString(self::USER_ID1));

        self::assertEquals(
            AccessRule::createForSpace(
                AccessRuleId::fromString(self::RULE1_SPACE1),
                $space1,
                IPFactory::parseAddressString('200.100.100.5')
            ),
            $this->ruleRepository->get(AccessRuleId::fromString(self::RULE1_SPACE1))
        );

        self::assertEquals(
            AccessRule::createForUser(
                AccessRuleId::fromString(self::RULE4_USER1),
                $user1,
                IPFactory::parseRangeString('127.0.0.1/24')
            ),
            $this->ruleRepository->get(AccessRuleId::fromString(self::RULE4_USER1))
        );
    }

    /** @test */
    public function it_get_all_from_space(): void
    {
        $this->assertIdsEquals([self::RULE1_SPACE1, self::RULE2_SPACE1, self::RULE4_USER1, self::RULE5_USER2], $this->ruleRepository->allOfSpace(SpaceId::fromString(self::SPACE_ID1)));
        $this->assertIdsEquals([self::RULE3_SPACE2], $this->ruleRepository->allOfSpace(SpaceId::fromString(self::SPACE_ID2)));
    }

    /** @test */
    public function it_get_all_from_user(): void
    {
        $this->assertIdsEquals([self::RULE4_USER1], $this->ruleRepository->allOfUser(FtpUserId::fromString(self::USER_ID1)));
        $this->assertIdsEquals([], $this->ruleRepository->allOfUser(FtpUserId::fromString(self::RULE5_USER2)));
    }

    /** @test */
    public function it_gets_if_has_allow_rules(): void
    {
        self::assertTrue($this->ruleRepository->hasAnyAllow(SpaceId::fromString(self::SPACE_ID2)));
        self::assertTrue($this->ruleRepository->hasAnyAllow(FtpUserId::fromString(self::USER_ID2)));

        self::assertFalse($this->ruleRepository->hasAnyAllow(SpaceId::fromString(self::SPACE_ID1)));
        self::assertFalse($this->ruleRepository->hasAnyAllow(FtpUserId::fromString(self::USER_ID1)));
    }
}
