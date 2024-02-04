<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\Webhosting\Ftp\User;

use Lifthill\Component\Common\Application\PasswordHasher;
use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use ParagonIE\HiddenString\HiddenString;
use ParkManager\Application\Command\Webhosting\Ftp\User\RegisterFtpUser;
use ParkManager\Application\Command\Webhosting\Ftp\User\RegisterFtpUserHandler;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\Webhosting\Ftp\FtpUser;
use ParkManager\Domain\Webhosting\Ftp\FtpUserId;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Mock\Domain\DomainName\DomainNameRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\FtpUserRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RegisterFtpUserHandlerTest extends TestCase
{
    private const DOMAIN_1 = '017d0b56-2d66-c966-b1ec-fc190f8a5c5f';

    private SpaceRepositoryMock $spaceRepository;
    private DomainNameRepositoryMock $domainNameRepository;
    private FtpUserRepositoryMock $userRepository;
    private RegisterFtpUserHandler $handler;

    protected function setUp(): void
    {
        $this->spaceRepository = new SpaceRepositoryMock([
            $space1 = SpaceRepositoryMock::createSpace(),
        ]);

        $this->domainNameRepository = new DomainNameRepositoryMock([
            DomainName::registerForSpace(
                DomainNameId::fromString(self::DOMAIN_1),
                $space1,
                new DomainNamePair('example1', 'com')
            ),
        ]);
        $this->spaceRepository->save($space1);
        $this->spaceRepository->resetRecordingState();

        $this->userRepository = new FtpUserRepositoryMock();
        $passwordHasher = new class() implements PasswordHasher {
            public function hash(HiddenString $password): string
            {
                return sprintf('hashed(%s)', $password->getString());
            }
        };

        $this->handler = new RegisterFtpUserHandler(
            $this->spaceRepository,
            $this->userRepository,
            $this->domainNameRepository,
            $passwordHasher
        );
    }

    /** @test */
    public function it_registers_an_ftp_user(): void
    {
        $this->handler->__invoke(
            new RegisterFtpUser(
                $id = FtpUserId::fromString('017d0ea3-d7cf-400e-681a-58985ac54bde'),
                $space = SpaceId::fromString(SpaceRepositoryMock::ID1),
                $username = 'webmasteres',
                $domain = DomainNameId::fromString(self::DOMAIN_1),
                new HiddenString('Nevergonnagiveyup'),
                $homeDir = '/public'
            )
        );

        $this->userRepository->assertEntitiesCountWasSaved(1);
        $this->userRepository->assertEntitiesWereSaved([
            new FtpUser(
                $id,
                $this->spaceRepository->get($space),
                $username,
                'hashed(Nevergonnagiveyup)',
                $this->domainNameRepository->get($domain),
                $homeDir
            ),
        ]);
    }

    /** @test */
    public function it_registers_an_ftp_user_without_homedir(): void
    {
        $this->handler->__invoke(
            new RegisterFtpUser(
                $id = FtpUserId::fromString('017d0ea3-d7cf-400e-681a-58985ac54bde'),
                $space = SpaceId::fromString(SpaceRepositoryMock::ID1),
                $username = 'webmasteres',
                $domain = DomainNameId::fromString(self::DOMAIN_1),
                new HiddenString('Nevergonnagiveyup'),
            )
        );

        $this->userRepository->assertEntitiesCountWasSaved(1);
        $this->userRepository->assertEntitiesWereSaved([
            new FtpUser(
                $id,
                $this->spaceRepository->get($space),
                $username,
                'hashed(Nevergonnagiveyup)',
                $this->domainNameRepository->get($domain),
            ),
        ]);
    }
}
