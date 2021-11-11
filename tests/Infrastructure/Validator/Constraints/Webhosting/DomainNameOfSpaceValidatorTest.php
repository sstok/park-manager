<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Validator\Constraints\Webhosting;

use ParkManager\Application\Command\Webhosting\Ftp\User\ChangeFtpUserUsername;
use ParkManager\Application\Service\RepositoryLocator;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\Webhosting\Ftp\FtpUser;
use ParkManager\Domain\Webhosting\Ftp\FtpUserId;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Infrastructure\Validator\Constraints\Webhosting\DomainNameOfSpace;
use ParkManager\Infrastructure\Validator\Constraints\Webhosting\DomainNameOfSpaceValidator;
use ParkManager\Tests\Mock\Domain\DomainName\DomainNameRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\FtpUserRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @internal
 */
final class DomainNameOfSpaceValidatorTest extends ConstraintValidatorTestCase
{
    public const DOMAIN_1 = '017d0b56-2d66-c966-b1ec-fc190f8a5c5f';
    public const DOMAIN_2 = '017d0b56-bc92-1f70-3046-d4642ada26c8';

    public const USER_ID1 = '017d0f7d-e547-7524-1c6d-5d679e9780d2';
    public const USER_ID2 = '017d0f8e-02de-b72c-0041-fcc963fc6c1d';

    private SpaceRepositoryMock $spaceRepository;
    private DomainNameRepositoryMock $domainNameRepository;

    protected function createValidator(): DomainNameOfSpaceValidator
    {
        $this->spaceRepository = new SpaceRepositoryMock([
            $space1 = SpaceRepositoryMock::createSpace(),
            $space2 = SpaceRepositoryMock::createSpace('017d0b54-f7f7-074a-93c3-118c865bab5f'),
        ]);

        $this->domainNameRepository = new DomainNameRepositoryMock([
            $domainName1 = DomainName::registerForSpace(
                DomainNameId::fromString(self::DOMAIN_1),
                $space1,
                new DomainNamePair('example1', 'com')
            ),
            $domainName2 = DomainName::registerForSpace(
                DomainNameId::fromString(self::DOMAIN_2),
                $space2,
                new DomainNamePair('example2', 'net')
            ),
        ]);
        $this->spaceRepository->save($space1);
        $this->spaceRepository->save($space2);
        $this->spaceRepository->resetRecordingState();

        $container = new Container();
        $container->set(FtpUser::class, new FtpUserRepositoryMock([
            new FtpUser(FtpUserId::fromString(self::USER_ID1), $space1, 'user1', 'do not trust', $domainName1),
            new FtpUser(FtpUserId::fromString(self::USER_ID2), $space2, 'user1', 'do not trust', $domainName2),
        ]));

        return new DomainNameOfSpaceValidator(
            $this->domainNameRepository,
            $this->spaceRepository,
            new RepositoryLocator($container),
        );
    }

    /** @test */
    public function it_ignores_null(): void
    {
        $this->validator->validate(null, new DomainNameOfSpace());

        $this->assertNoViolation();
    }

    /** @test */
    public function it_passes_with_domain_belonging_to_same_space(): void
    {
        $command = new class() {
            public SpaceId $space;
            public DomainNameId $domainName;

            public function __construct(
                $space = SpaceRepositoryMock::ID1,
                $domainName = DomainNameOfSpaceValidatorTest::DOMAIN_1,
            ) {
                $this->space = SpaceId::fromString($space);
                $this->domainName = DomainNameId::fromString($domainName);
            }
        };

        $this->validator->validate($command, new DomainNameOfSpace());

        $this->assertNoViolation();
    }

    /** @test */
    public function it_passes_with_domain_pair_belonging_to_same_space(): void
    {
        $command = new class() {
            public SpaceId $space;
            public DomainNamePair $domainName;

            public function __construct(
                $space = SpaceRepositoryMock::ID1,
                $domainName = DomainNameOfSpaceValidatorTest::DOMAIN_1,
            ) {
                $this->space = SpaceId::fromString($space);
                $this->domainName = new DomainNamePair('example1', 'com');
            }
        };

        $this->validator->validate($command, new DomainNameOfSpace());

        $this->assertNoViolation();
    }

    /** @test */
    public function it_passes_with_domain_being_null(): void
    {
        $command = new class() {
            public SpaceId $space;

            public function __construct(
                $space = SpaceRepositoryMock::ID1,
                public ?DomainNameId $domainName = null,
            ) {
                $this->space = SpaceId::fromString($space);
            }
        };

        $this->validator->validate($command, new DomainNameOfSpace());

        $this->assertNoViolation();
    }

    /** @test */
    public function it_fails_with_domain_belonging_to_different_space(): void
    {
        $command = new class() {
            public SpaceId $space;
            public DomainNameId $domainName;

            public function __construct(
                $space = SpaceRepositoryMock::ID1,
                $domainName = DomainNameOfSpaceValidatorTest::DOMAIN_2,
            ) {
                $this->space = SpaceId::fromString($space);
                $this->domainName = DomainNameId::fromString($domainName);
            }
        };

        $this->validator->validate($command, $constraint = new DomainNameOfSpace());

        $this->buildViolation('not_owned_by_same_space')
            ->atPath('property.path.' . $constraint->domainProperty)
            ->setParameter('{ domain_name }', 'example2.net')
            ->setInvalidValue($command)
            ->assertRaised()
        ;
    }

    /** @test */
    public function it_fails_with_domain_belonging_to_different_space_and_command_without_space_id(): void
    {
        $command = new ChangeFtpUserUsername(
            FtpUserId::fromString(self::USER_ID1),
            'user2',
            DomainNameId::fromString(self::DOMAIN_2)
        );

        $this->validator->validate($command, $constraint = new DomainNameOfSpace(spaceProperty: '@id.space'));

        $this->buildViolation('not_owned_by_same_space')
            ->atPath('property.path.' . $constraint->domainProperty)
            ->setParameter('{ domain_name }', 'example2.net')
            ->setInvalidValue($command)
            ->assertRaised()
        ;
    }
}
