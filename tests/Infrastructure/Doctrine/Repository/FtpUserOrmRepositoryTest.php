<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Doctrine\Repository;

use ParkManager\Domain\Webhosting\Ftp\FtpUser;
use ParkManager\Domain\Webhosting\Ftp\FtpUserId;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Infrastructure\Doctrine\Repository\FtpUserOrmRepository;

/**
 * @internal
 *
 * @group functional
 */
final class FtpUserOrmRepositoryTest extends WebhostingRepositoryTestCase
{
    private const USER_ID1 = '84bdf8df-a347-44bf-96c9-b163a99cd925';
    private const USER_ID2 = '2f41666b-b670-4dab-9ffb-65c51dfd53df';
    private const USER_ID3 = '017d03ba-513e-b09f-c09c-1c827c40b533';

    private FtpUserOrmRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $space1 = $this->spaceRepository->get(SpaceId::fromString(self::SPACE_ID1));
        $space2 = $this->spaceRepository->get(SpaceId::fromString(self::SPACE_ID2));

        $domainSpace1 = $this->domainRepository->getPrimaryOf($space1->id);
        $domainSpace2 = $this->domainRepository->getPrimaryOf($space2->id);

        $em = $this->getEntityManager();
        $em->beginTransaction();

        $this->repository = new FtpUserOrmRepository($em);
        $this->repository->save(
            new FtpUser(
                FtpUserId::fromString(self::USER_ID1),
                $space1,
                'user1',
                'ElHomoWasHere',
                $domainSpace1
            )
        );
        $this->repository->save(
            new FtpUser(
                FtpUserId::fromString(self::USER_ID2),
                $space1,
                'user2',
                'Visaaaaa',
                $domainSpace1
            )
        );

        $this->repository->save(
            new FtpUser(
                FtpUserId::fromString(self::USER_ID3),
                $space2,
                'user1',
                '@internal-rotation-is-undoing-your-gains',
                $domainSpace2
            )
        );

        $em->flush();
        $em->commit();
        $em->clear();
    }

    /** @test */
    public function it_gets_user(): void
    {
        $space1 = $this->spaceRepository->get(SpaceId::fromString(self::SPACE_ID1));
        $domainSpace1 = $this->domainRepository->getPrimaryOf($space1->id);

        $expectedUser = new FtpUser(
            FtpUserId::fromString(self::USER_ID1),
            $space1,
            'user1',
            'ElHomoWasHere',
            $domainSpace1
        );

        self::assertEquals($expectedUser, $this->repository->get(FtpUserId::fromString(self::USER_ID1)));
    }

    /** @test */
    public function it_gets_all_users(): void
    {
        $this->assertIdsEquals([self::USER_ID1, self::USER_ID2], $this->repository->all(SpaceId::fromString(self::SPACE_ID1)));
        $this->assertIdsEquals([self::USER_ID3], $this->repository->all(SpaceId::fromString(self::SPACE_ID2)));
    }
}
