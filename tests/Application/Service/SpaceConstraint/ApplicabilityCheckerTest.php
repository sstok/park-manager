<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Service\SpaceConstraint;

use ParkManager\Application\Service\SpaceConstraint\ApplicabilityChecker;
use ParkManager\Domain\ByteSize;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\DBConstraints;
use ParkManager\Domain\Webhosting\Constraint\EmailConstraints;
use ParkManager\Domain\Webhosting\Email\Mailbox;
use ParkManager\Domain\Webhosting\Email\MailboxId;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Mock\Application\Service\CurrentStorageUsageRetrieverMock;
use ParkManager\Tests\Mock\Domain\Webhosting\MailboxRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use ParkManager\Tests\SpaceConstraintsEquals;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ApplicabilityCheckerTest extends TestCase
{
    public const SPACE_ID1 = SpaceRepositoryMock::ID1;
    public const SPACE_ID2 = '1f70da43-1a2a-49ec-8621-57fc50f080ff';

    public const MAILBOX_ID1 = '5e55d91e-bffb-4ca7-bdfc-baba007349b9';
    public const MAILBOX_ID2 = '09eeded5-6db2-48ba-9397-0f93111eef5f';

    private SpaceRepositoryMock $spaceRepository;
    private ApplicabilityChecker $applicabilityChecker;

    protected function setUp(): void
    {
        $this->spaceRepository = new SpaceRepositoryMock([
            $space1 = SpaceRepositoryMock::createSpace(self::SPACE_ID1),
            $space2 = SpaceRepositoryMock::createSpace(
                self::SPACE_ID2,
                constraints: new Constraints(
                    [
                        'monthlyTraffic' => 100,
                        'storageSize' => new ByteSize(100, 'GiB'),
                        'email' => new EmailConstraints([
                            'maxStorageSize' => new ByteSize(10, 'GiB'),
                            'maximumAddressCount' => 200,
                            'maximumMailboxCount' => 5,
                            'maximumForwardCount' => 8,
                            'spamFilterCount' => 5,
                            'mailListCount' => 3,
                        ]),
                        'database' => new DBConstraints([
                            'providedStorageSize' => new ByteSize(200, 'MiB'),
                            'maximumAmountPerType' => 10,
                            'enabledPgsql' => true,
                            'enabledMysql' => false,
                        ]),
                    ]
                )
            ),
        ]);

        $domainName1 = DomainName::registerForSpace(DomainNameId::create(), $space1, new DomainNamePair('example', 'com'));
        $domainName2 = DomainName::registerForSpace(DomainNameId::create(), $space2, new DomainNamePair('axample', 'net'));

        $mailboxRepository = new MailboxRepositoryMock(
            [
                // Space1
                new Mailbox(MailboxId::create(), $space1, 'jane', $domainName1, ByteSize::inf(), 'nope'),
                new Mailbox(MailboxId::create(), $space1, 'joe', $domainName1, ByteSize::inf(), 'nope'),

                // Space2
                new Mailbox(MailboxId::fromString(self::MAILBOX_ID1), $space2, 'hom', $domainName2, new ByteSize(10, 'GiB'), 'nope'),
                new Mailbox(MailboxId::fromString(self::MAILBOX_ID2), $space2, 'doe', $domainName2, new ByteSize(2, 'GiB'), 'nope'),
            ]
        );
        $storageUsageRetriever = new CurrentStorageUsageRetrieverMock(
            [
                self::SPACE_ID1 => new ByteSize(500, 'MiB'),
                self::SPACE_ID2 => new ByteSize(9, 'GiB'),
            ],
            [
                self::MAILBOX_ID1 => new ByteSize(10, 'GiB'),
                self::MAILBOX_ID2 => new ByteSize(1, 'GiB'),
            ]
        );

        $this->applicabilityChecker = new ApplicabilityChecker($this->spaceRepository, $mailboxRepository, $storageUsageRetriever);
    }

    /** @test */
    public function it_returns_same_instance_when_equals(): void
    {
        $constraints1 = $this->spaceRepository->get(SpaceId::fromString(self::SPACE_ID1))->constraints;
        $constraints2 = $this->spaceRepository->get(SpaceId::fromString(self::SPACE_ID2))->constraints;

        self::assertSame($constraints1, $this->applicabilityChecker->getApplicable(SpaceId::fromString(self::SPACE_ID1), $constraints1));
        self::assertSame($constraints2, $this->applicabilityChecker->getApplicable(SpaceId::fromString(self::SPACE_ID2), $constraints2));
    }

    /**
     * @test
     * @dataProvider provideNewConstraintsExpectations
     */
    public function returns_new_value_when_higher_or_equals_to_current_usage(Constraints $newConstraints, Constraints $expected): void
    {
        $applicable = $this->applicabilityChecker->getApplicable(SpaceId::fromString(self::SPACE_ID2), $newConstraints);

        self::assertThat($applicable, new SpaceConstraintsEquals($expected));

        // Changes should equal to make the application process easier.
        self::assertEquals($expected->changes, $applicable->changes);
        self::assertEquals($expected->email->changes, $applicable->email->changes);
        self::assertEquals($expected->database->changes, $applicable->database->changes);
    }

    public function provideNewConstraintsExpectations(): iterable
    {
        $current = new Constraints(
            [
                'monthlyTraffic' => 100,
                'storageSize' => new ByteSize(100, 'GiB'),
                'email' => new EmailConstraints([
                    'maxStorageSize' => new ByteSize(10, 'GiB'),
                    'maximumAddressCount' => 200,
                    'maximumMailboxCount' => 5,
                    'maximumForwardCount' => 8,
                    'spamFilterCount' => 5,
                    'mailListCount' => 3,
                ]),
                'database' => new DBConstraints([
                    'providedStorageSize' => new ByteSize(200, 'MiB'),
                    'maximumAmountPerType' => 10,
                    'enabledPgsql' => true,
                    'enabledMysql' => false,
                ]),
            ]
        );
        $emailCurrent = $current->email;
        $dbCurrent = $current->database;

        yield 'Unchanged' => [
            $current->setMonthlyTraffic(100),
            $current,
        ];

        yield 'StorageSize' => [
            $current->setStorageSize(new ByteSize(15, 'GiB')),
            $current->setStorageSize(new ByteSize(22549626880, 'B')), // 21.0009766 GiB
        ];

        yield 'StorageSize above current usage' => [
            $constraints = $current->setStorageSize(new ByteSize(22, 'GiB')),
            $constraints,
        ];

        yield 'Monthly traffic (more)' => [
            $constraints = $current->setMonthlyTraffic(200),
            $constraints,
        ];

        yield 'Monthly traffic (less)' => [
            $constraints = $current->setMonthlyTraffic(10),
            $constraints,
        ];

        yield 'Email (lesser)' => [
            $constraints = $current->setEmail(
                $emailCurrent
                    ->setMaxStorageSize(new ByteSize(1, 'GiB'))
                    ->setMaximumAddressCount(0)
                    ->setMaximumMailboxCount(3)
                    ->setMaximumForwardCount(5)
                    ->setSpamFilterCount(5)
                    ->setMailListCount(2)
            ),
            $constraints,
        ];

        yield 'Email (more)' => [
            $constraints = $current->setEmail(
                $emailCurrent
                    ->setMaxStorageSize(new ByteSize(20, 'GiB'))
                    ->setMaximumAddressCount(400)
                    ->setMaximumMailboxCount(8)
                    ->setMaximumForwardCount(10)
                    ->setSpamFilterCount(7)
                    ->setMailListCount(5)
            ),
            $constraints,
        ];

        yield 'DB (more)' => [
            $constraints = $current->setDatabase(
                $dbCurrent
                    ->setProvidedStorageSize(new ByteSize(20, 'GiB'))
                    ->setMaximumAmountPerType(40)
            ),
            $constraints,
        ];

        yield 'DB (less)' => [
            $constraints = $current->setDatabase(
                $dbCurrent
                    ->setProvidedStorageSize(new ByteSize(10, 'MiB'))
                    ->setMaximumAmountPerType(1)
                    ->disablePgsql()
                    ->disableMysql()
            ),
            $constraints,
        ];
    }
}
