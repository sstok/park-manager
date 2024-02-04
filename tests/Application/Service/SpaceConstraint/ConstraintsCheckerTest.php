<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Service\SpaceConstraint;

use Lifthill\Component\Common\Domain\Model\ByteSize;
use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use Lifthill\Component\Common\Domain\Model\EmailAddress;
use ParkManager\Application\Service\SpaceConstraint\ConstraintsChecker;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\EmailConstraints;
use ParkManager\Domain\Webhosting\Constraint\Exception\ConstraintExceeded;
use ParkManager\Domain\Webhosting\Email\Forward;
use ParkManager\Domain\Webhosting\Email\ForwardId;
use ParkManager\Domain\Webhosting\Email\Mailbox;
use ParkManager\Domain\Webhosting\Email\MailboxId;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Mock\Application\Service\StorageUsageMock;
use ParkManager\Tests\Mock\Domain\Webhosting\ForwardRepositoryMock as EmailForwardRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\MailboxRepositoryMock;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ConstraintsCheckerTest extends TestCase
{
    public const SPACE_ID1 = SpaceRepositoryMock::ID1;
    public const SPACE_ID2 = '1f70da43-1a2a-49ec-8621-57fc50f080ff';

    public const MAILBOX_ID1 = '5e55d91e-bffb-4ca7-bdfc-baba007349b9';
    public const MAILBOX_ID2 = '09eeded5-6db2-48ba-9397-0f93111eef5f';

    private SpaceRepositoryMock $spaceRepository;
    private MailboxRepositoryMock $mailboxRepository;
    private EmailForwardRepositoryMock $emailForwardRepository;
    private StorageUsageMock $storageUsageRetriever;
    private ConstraintsChecker $constraintChecker;

    protected function setUp(): void
    {
        $this->spaceRepository = new SpaceRepositoryMock([
            $space1 = SpaceRepositoryMock::createSpace(),
            $space2 = SpaceRepositoryMock::createSpace(
                self::SPACE_ID2,
                constraints: new Constraints(
                    [
                        'storageSize' => new ByteSize(100, 'GiB'),
                        'email' => new EmailConstraints([
                            'maxStorageSize' => new ByteSize(10, 'GiB'),
                            'maximumMailboxCount' => 5,
                            'maximumForwardCount' => 8,
                        ]),
                    ]
                )
            ),
        ]);

        $domainName1 = DomainName::registerForSpace(DomainNameId::create(), $space1, new DomainNamePair('example', 'com'));
        $domainName2 = DomainName::registerForSpace(DomainNameId::create(), $space2, new DomainNamePair('axample', 'net'));

        $this->mailboxRepository = new MailboxRepositoryMock(
            [
                // Space1
                new Mailbox(MailboxId::create(), $space1, 'jane', $domainName1, ByteSize::inf(), 'nope'),
                new Mailbox(MailboxId::create(), $space1, 'joe', $domainName1, ByteSize::inf(), 'nope'),

                // Space2
                new Mailbox(MailboxId::fromString(self::MAILBOX_ID1), $space2, 'hom', $domainName2, new ByteSize(10, 'GiB'), 'nope'),
                new Mailbox(MailboxId::fromString(self::MAILBOX_ID2), $space2, 'doe', $domainName2, new ByteSize(2, 'GiB'), 'nope'),
            ]
        );
        $this->emailForwardRepository = new EmailForwardRepositoryMock([
            // Space1
            Forward::toAddress(ForwardId::create(), $space1, 'info', $domainName1, new EmailAddress('heaven@hell.inc.org')),
            Forward::toAddress(ForwardId::create(), $space1, 'support', $domainName1, new EmailAddress('noreply@exampel.com')),

            // Space2
            Forward::toAddress(ForwardId::create(), $space2, 'support', $domainName2, new EmailAddress('noreply@exampel.com')),
            Forward::toAddress(ForwardId::create(), $space2, 'nope-reply', $domainName2, new EmailAddress('hello@exampel.com')),
            Forward::toAddress(ForwardId::create(), $space2, 'secret', $domainName2, new EmailAddress('security@exampel.com')),
        ]);
        $this->storageUsageRetriever = new StorageUsageMock(
            [
                self::SPACE_ID1 => new ByteSize(500, 'MiB'),
                self::SPACE_ID2 => new ByteSize(9, 'GiB'),
            ],
            [
                self::MAILBOX_ID1 => new ByteSize(10, 'GiB'),
                self::MAILBOX_ID2 => new ByteSize(1, 'GiB'),
            ]
        );

        $this->constraintChecker = new ConstraintsChecker(
            $this->spaceRepository,
            $this->mailboxRepository,
            $this->emailForwardRepository,
            $this->storageUsageRetriever
        );
    }

    /** @test */
    public function returns_storage_size_is_not_reached_when_unlimited(): void
    {
        $spaceId = SpaceId::fromString(self::SPACE_ID1);

        $this->storageUsageRetriever->setDiskUsageOf($spaceId, new ByteSize(10000, 'GiB'));
        self::assertFalse($this->constraintChecker->isStorageSizeReached($spaceId));
    }

    /** @test */
    public function returns_storage_size_is_reached_when_exceeded(): void
    {
        $spaceId1 = SpaceId::fromString(self::SPACE_ID1);
        $spaceId2 = SpaceId::fromString(self::SPACE_ID2);

        $this->storageUsageRetriever->setDiskUsageOf($spaceId2, new ByteSize(1000, 'GiB'));
        self::assertFalse($this->constraintChecker->isStorageSizeReached($spaceId1));
        self::assertTrue($this->constraintChecker->isStorageSizeReached($spaceId2));

        $this->storageUsageRetriever->setDiskUsageOf($spaceId2, (new ByteSize(1000, 'GiB'))->increase(new ByteSize(2, 'MiB')));
        self::assertTrue($this->constraintChecker->isStorageSizeReached($spaceId2));

        $this->storageUsageRetriever->setDiskUsageOf($spaceId2, new ByteSize(9, 'GiB'));
        self::assertFalse($this->constraintChecker->isStorageSizeReached($spaceId2));
    }

    /**
     * @test
     *
     * @doesNotPerformAssertions
     */
    public function allows_new_addresses_when_amount_is_unlimited(): void
    {
        $space = $this->spaceRepository->get(SpaceId::fromString(self::SPACE_ID2));
        $space->assignCustomConstraints($space->constraints->setStorageSize(ByteSize::inf()));
        $space->assignCustomConstraints($space->constraints->setEmail(new EmailConstraints()));
        $space->setWebQuota(new ByteSize(1000, 'GB')); // As there is no total limit this value has no effect.
        $this->spaceRepository->save($space);

        $this->constraintChecker->allowNewMailboxes(
            SpaceId::fromString(self::SPACE_ID1),
            [
                'l1@example.com' => ByteSize::inf(),
                'l2@example.com' => ByteSize::inf(),
                'l3@example.com' => new ByteSize(10, 'GiB'),
                'l4@example.com' => ByteSize::inf(),
                'l5@example.com' => ByteSize::inf(),
            ]
        );

        $this->constraintChecker->allowNewEmailForward(
            SpaceId::fromString(self::SPACE_ID1),
            [
                'l1@example.com',
                'l2@example.com',
                'l3@example.com',
                'l4@example.com',
                'l5@example.com',
            ]
        );

        $this->constraintChecker->allowNewMailboxes(
            SpaceId::fromString(self::SPACE_ID2),
            [
                'l1@example.com' => new ByteSize(10, 'MiB'),
                'l2@example.com' => new ByteSize(10, 'MiB'),
                'l3@example.com' => new ByteSize(10, 'MiB'),
            ]
        );

        $this->constraintChecker->allowNewEmailForward(
            SpaceId::fromString(self::SPACE_ID2),
            [
                'l1@example.com',
                'l2@example.com',
                'l3@example.com',
                'l4@example.com',
                'l5@example.com',
            ]
        );

        $this->constraintChecker->allowNewMailboxes(
            SpaceId::fromString(self::SPACE_ID2),
            [
                'l1@example.com' => new ByteSize(10000, 'GiB'),
            ]
        );

        $this->setEmailConstraintsForSpace2([
            'maximumMailboxCount' => 5,
            'maximumForwardCount' => 5,
            'maximumAddressCount' => -1,
        ]);

        // maximumMailboxCount is 5, but maximumAddressCount is unlimited
        $this->constraintChecker->allowNewMailboxes(
            SpaceId::fromString(self::SPACE_ID2),
            [
                'l1@example.com' => new ByteSize(100, 'GiB'),
                'l2@example.com' => new ByteSize(100, 'GiB'),
                'l3@example.com' => new ByteSize(100, 'GiB'),
                'l4@example.com' => new ByteSize(100, 'GiB'),
                'l5@example.com' => new ByteSize(100, 'GiB'),
                'l6@example.com' => new ByteSize(100, 'GiB'),
            ]
        );

        // maximumForwardCount is 5, but maximumAddressCount is unlimited
        $this->constraintChecker->allowNewEmailForward(
            SpaceId::fromString(self::SPACE_ID2),
            [
                'l1@example.com',
                'l2@example.com',
                'l3@example.com',
                'l4@example.com',
                'l5@example.com',
                'l6@example.com',
            ]
        );
    }

    /**
     * @param array<string, ByteSize|int> $emailConstraints
     */
    private function setEmailConstraintsForSpace2(array $emailConstraints): void
    {
        $space = $this->spaceRepository->get(SpaceId::fromString(self::SPACE_ID2));
        $space->assignCustomConstraints($space->constraints->setStorageSize(ByteSize::inf()));
        $space->assignCustomConstraints($space->constraints->setEmail(new EmailConstraints($emailConstraints)));
        $this->spaceRepository->save($space);
    }

    /**
     * @test
     *
     * @doesNotPerformAssertions
     */
    public function allows_new_addresses_when_amount_is_not_reached_yet(): void
    {
        $this->setEmailConstraintsForSpace2([
            'maximumAddressCount' => 8,
            'maximumMailboxCount' => 5, // ignored
            'maximumForwardCount' => 4, // ignored
        ]);

        $this->constraintChecker->allowNewEmailForward(SpaceId::fromString(self::SPACE_ID2), [
            'l5@example.com',
            'l6@example.com',
            'l7@example.com',
        ]);

        $this->constraintChecker->allowNewMailboxes(
            SpaceId::fromString(self::SPACE_ID2),
            [
                'l1@example.com' => new ByteSize(100, 'GiB'),
                'l2@example.com' => new ByteSize(100, 'GiB'),
            ]
        );

        $this->setEmailConstraintsForSpace2([
            'maximumMailboxCount' => 6,
            'maximumForwardCount' => 5,
        ]);

        $this->constraintChecker->allowNewEmailForward(SpaceId::fromString(self::SPACE_ID2), [
            'l1@example.com',
            'l2@example.com',
        ]);

        $this->constraintChecker->allowNewMailboxes(
            SpaceId::fromString(self::SPACE_ID2),
            [
                'l1@example.com' => new ByteSize(100, 'GiB'),
                'l2@example.com' => new ByteSize(100, 'GiB'),
                'l3@example.com' => new ByteSize(100, 'GiB'),
                'l4@example.com' => new ByteSize(100, 'GiB'),
            ]
        );
    }

    /**
     * @test
     *
     * @dataProvider provideRestricts_new_addresses_constraints_are_reachedCases
     *
     * @param array<string, ByteSize>   $mailboxes
     * @param array<int|string, string> $forwards
     */
    public function restricts_new_addresses_constraints_are_reached(array $mailboxes, array $forwards, ?EmailConstraints $emailConstraints, ConstraintExceeded $exception): void
    {
        $id = SpaceId::fromString(self::SPACE_ID2);

        if ($emailConstraints !== null) {
            $space = $this->spaceRepository->get($id);
            $space->assignCustomConstraints($space->constraints->setEmail($emailConstraints));
            $this->spaceRepository->save($space);
        }

        try {
            $this->constraintChecker->allowNewMailboxes($id, $mailboxes);
            $this->constraintChecker->allowNewEmailForward($id, $forwards);

            self::fail('Exception was expected');
        } catch (ConstraintExceeded $e) {
            self::assertEquals($exception, $e);
            self::assertStringStartsWith('space_constraint_exceeded.', $exception->getMessage());
            self::assertStringStartsWith('space_constraint_exceeded.', (string) $exception->getTranslatorMsg());
            self::assertNotEmpty($exception->getTranslatorMsg()->getParameters());
        }
    }

    /**
     * @return \Generator<string, array<int, mixed>>
     */
    public static function provideRestricts_new_addresses_constraints_are_reachedCases(): iterable
    {
        yield 'mailbox amount exceeded with no forwards' => [
            [
                'l1@example.com' => new ByteSize(10, 'MiB'),
                'l2@example.com' => new ByteSize(10, 'MiB'),
                'l3@example.com' => new ByteSize(10, 'MiB'),
            ],
            [],
            new EmailConstraints([
                'maxStorageSize' => new ByteSize(100, 'GiB'),
                'maximumMailboxCount' => 3,
                'maximumForwardCount' => 1,
            ]),
            ConstraintExceeded::mailboxCount(3, 5),
        ];

        yield 'email forwards amount exceeded' => [
            [
                'l1@example.com' => new ByteSize(10, 'MiB'),
                'l2@example.com' => new ByteSize(10, 'MiB'),
            ],
            [
                'l1@example.com',
            ],
            new EmailConstraints([
                'maxStorageSize' => new ByteSize(100, 'GiB'),
                'maximumMailboxCount' => 4,
                'maximumForwardCount' => 3,
            ]),
            ConstraintExceeded::emailForwardCount(3, 4),
        ];

        yield 'email forwards amount exceeded 2' => [
            [
                'l1@example.com' => new ByteSize(10, 'MiB'),
                'l2@example.com' => new ByteSize(10, 'MiB'),
            ],
            [
                'l1@example.com',
                'l2@example.com',
            ],
            new EmailConstraints([
                'maxStorageSize' => new ByteSize(100, 'GiB'),
                'maximumMailboxCount' => 4,
                'maximumForwardCount' => 3,
            ]),
            ConstraintExceeded::emailForwardCount(3, 5),
        ];

        yield 'address amount exceeded (by mailbox)' => [
            [
                'l1@example.com' => new ByteSize(10, 'MiB'),
                'l2@example.com' => new ByteSize(10, 'MiB'),
                'l3@example.com' => new ByteSize(10, 'MiB'),
            ],
            [],
            new EmailConstraints([
                'maxStorageSize' => new ByteSize(100, 'GiB'),
                'maximumAddressCount' => 7,
            ]),
            ConstraintExceeded::emailAddressesCount(7, 8),
        ];

        yield 'address amount exceeded (by forwards)' => [
            [],
            [
                't1@example.com',
                't2@example.com',
                't3@example.com',
                't4@example.com',
            ],
            new EmailConstraints([
                'maxStorageSize' => new ByteSize(100, 'GiB'),
                'maximumAddressCount' => 7,
            ]),
            ConstraintExceeded::emailAddressesCount(7, 9),
        ];

        yield 'mailbox size exceeds space limit' => [
            [
                'l1@example.com' => new ByteSize(10000, 'GiB'),
            ],
            [],
            new EmailConstraints(),
            ConstraintExceeded::mailboxStorageSizeRange(new EmailAddress('l1@example.com'), new ByteSize(10000, 'GiB'), new ByteSize(1, 'MiB'), new ByteSize(79, 'GiB')),
        ];

        yield 'mailbox size exceeds total limit' => [
            [
                'l1@example.com' => new ByteSize(10, 'GiB'),
                'l2@example.com' => new ByteSize(900, 'GiB'),
            ],
            [],
            new EmailConstraints(),
            ConstraintExceeded::mailboxStorageSizeRange(new EmailAddress('l2@example.com'), new ByteSize(900, 'GiB'), new ByteSize(1, 'MiB'), new ByteSize(69, 'GiB')),
        ];

        yield 'mailbox size exceeds maximum mailbox limit' => [
            [
                'l1@example.com' => new ByteSize(12, 'GiB'),
                'l2@example.com' => new ByteSize(200, 'GiB'),
            ],
            [],
            new EmailConstraints([
                'maxStorageSize' => new ByteSize(10, 'GiB'),
            ]),
            ConstraintExceeded::mailboxStorageSizeRange(new EmailAddress('l1@example.com'), new ByteSize(12, 'GiB'), new ByteSize(1, 'MiB'), new ByteSize(10, 'GiB')),
        ];

        yield 'mailbox size to small' => [
            [
                'l1@example.com' => new ByteSize(12, 'KiB'),
                'l2@example.com' => new ByteSize(200, 'GiB'),
            ],
            [],
            new EmailConstraints([
                'maxStorageSize' => new ByteSize(10, 'GiB'),
            ]),
            ConstraintExceeded::mailboxStorageSizeRange(new EmailAddress('l1@example.com'), new ByteSize(12, 'KiB'), new ByteSize(1, 'MiB'), new ByteSize(10, 'GiB')),
        ];
    }

    /**
     * @test
     *
     * @dataProvider provideRestricts_new_addresses_size_constraints_with_web_quotaCases
     *
     * @param array<string, ByteSize> $mailboxes
     */
    public function restricts_new_addresses_size_constraints_with_web_quota(array $mailboxes, ByteSize $quota, ?EmailConstraints $emailConstraints, ConstraintExceeded $exception): void
    {
        $id = SpaceId::fromString(self::SPACE_ID2);

        $space = $this->spaceRepository->get($id);
        $space->assignCustomConstraints($space->constraints->setEmail($emailConstraints));
        $space->setWebQuota($quota);
        $this->spaceRepository->save($space);

        try {
            $this->constraintChecker->allowNewMailboxes($id, $mailboxes);

            self::fail('Exception was expected');
        } catch (ConstraintExceeded $e) {
            self::assertEquals($exception, $e);
        }
    }

    /**
     * @return \Generator<string, array<int, mixed>>
     */
    public static function provideRestricts_new_addresses_size_constraints_with_web_quotaCases(): iterable
    {
        // Total limit: 100 GB
        // Mailbox current storage 11 GB (10 + 1)
        // Using already 11 GB of storage, REST: 89

        yield 'total space is used' => [
            [
                'l1@example.com' => new ByteSize(10, 'GiB'),
                'l2@example.com' => new ByteSize(10, 'GiB'),
            ],
            new ByteSize(89, 'GiB'),
            new EmailConstraints([
                'maxStorageSize' => new ByteSize(10, 'GiB'),
            ]),
            ConstraintExceeded::mailboxStorageSizeRange(new EmailAddress('l1@example.com'), new ByteSize(10, 'GiB'), new ByteSize(1, 'MiB'), new ByteSize(0, 'b')),
        ];

        yield 'mailbox size, first requested, exceeds total free space' => [
            [
                'l1@example.com' => new ByteSize(19, 'GiB'),
            ],
            new ByteSize(70, 'GiB'),
            new EmailConstraints([
                'maxStorageSize' => new ByteSize(20, 'GiB'),
            ]),
            ConstraintExceeded::mailboxStorageSizeRange(new EmailAddress('l1@example.com'), new ByteSize(19, 'GiB'), new ByteSize(1, 'MiB'), new ByteSize(18, 'GiB')),
        ];

        yield 'mailbox size, requested second, exceeds total free space' => [
            [
                'l1@example.com' => new ByteSize(10, 'GiB'),
                'l2@example.com' => new ByteSize(10, 'GiB'),
            ],
            new ByteSize(70, 'GiB'),
            new EmailConstraints([
                'maxStorageSize' => new ByteSize(10, 'GiB'),
            ]),
            ConstraintExceeded::mailboxStorageSizeRange(new EmailAddress('l2@example.com'), new ByteSize(10, 'GiB'), new ByteSize(1, 'MiB'), new ByteSize(0, 'b')),
        ];
    }

    /**
     * @test
     *
     * @dataProvider provideMailbox_resize_constraintsCases
     */
    public function mailbox_resize_constraints(ByteSize $newSize, ?ByteSize $quota, ?ConstraintExceeded $exception): void
    {
        $space = $this->spaceRepository->get(SpaceId::fromString(self::SPACE_ID2));
        $space->assignCustomConstraints(
            $space->constraints->setEmail(
                new EmailConstraints([
                    'maxStorageSize' => new ByteSize(90, 'GiB'),
                ])
            )
        );

        if ($quota !== null) {
            $space->setWebQuota($quota);
        }

        $this->spaceRepository->save($space);

        try {
            $this->constraintChecker->allowMailboxSize(MailboxId::fromString(self::MAILBOX_ID1), $newSize);

            if ($exception) {
                self::fail('Exception was expected');
            }

            self::assertTrue(true);
        } catch (ConstraintExceeded $e) {
            self::assertEquals($exception, $e);
        }
    }

    /**
     * @return \Generator<string, array{0: ByteSize, 1: ByteSize|null, 2: ConstraintExceeded|null}>
     */
    public static function provideMailbox_resize_constraintsCases(): iterable
    {
        yield 'total space is used' => [
            new ByteSize(90, 'GiB'),
            new ByteSize(89, 'GiB'), // 100% used
            ConstraintExceeded::mailboxStorageResizeRange(new EmailAddress('hom@axample.net'), new ByteSize(90, 'GiB'), new ByteSize(1, 'MiB'), new ByteSize(0, 'b')),
        ];

        yield 'requested size is less than current usage' => [
            new ByteSize(9, 'GiB'),
            null,
            ConstraintExceeded::mailboxStorageResizeRange(new EmailAddress('hom@axample.net'), new ByteSize(9, 'GiB'), new ByteSize(10, 'GiB'), new ByteSize(79, 'Gib')),
        ];

        yield 'requested size is higher than maximum size' => [
            new ByteSize(100, 'GiB'),
            new ByteSize(1, 'GiB'),
            ConstraintExceeded::mailboxStorageResizeRange(new EmailAddress('hom@axample.net'), new ByteSize(100, 'GiB'), new ByteSize(10, 'GiB'), new ByteSize(87, 'Gib')),
        ];

        yield 'requested size is higher than maximum size (with full quota)' => [
            new ByteSize(100, 'GiB'),
            new ByteSize(89, 'GiB'),
            ConstraintExceeded::mailboxStorageResizeRange(new EmailAddress('hom@axample.net'), new ByteSize(100, 'GiB'), new ByteSize(10, 'GiB'), new ByteSize(0, 'Gib')),
        ];

        yield 'requested size is same as current' => [
            new ByteSize(10, 'GiB'),
            new ByteSize(89, 'GiB'),
            null,
        ];

        yield 'requested size is within acceptable bounds' => [
            new ByteSize(15, 'GiB'),
            new ByteSize(30, 'GiB'),
            null,
        ];
    }

    /**
     * @test
     *
     * @dataProvider provideDisk_resize_constraintsCases
     */
    public function disk_resize_constraints(ByteSize $newSize, ?ConstraintExceeded $exception): void
    {
        try {
            $this->constraintChecker->allowHostingSize(SpaceId::fromString(self::SPACE_ID2), $newSize);

            if ($exception) {
                self::fail('Exception was expected');
            }

            self::assertTrue(true);
        } catch (ConstraintExceeded $e) {
            self::assertEquals($exception, $e);
        }
    }

    /**
     * @return \Generator<string, array{0: ByteSize, 1: ConstraintExceeded|null}>
     */
    public static function provideDisk_resize_constraintsCases(): iterable
    {
        yield 'requested size is less than current usage' => [
            new ByteSize(8, 'GiB'),
            ConstraintExceeded::diskStorageSizeRange(SpaceId::fromString(self::SPACE_ID2), new ByteSize(8, 'GiB'), new ByteSize(9, 'GiB'), new ByteSize(79, 'Gib')),
        ];

        yield 'requested size is less than total free space' => [
            new ByteSize(90, 'GiB'),
            ConstraintExceeded::diskStorageSizeRange(SpaceId::fromString(self::SPACE_ID2), new ByteSize(90, 'GiB'), new ByteSize(9, 'GiB'), new ByteSize(79, 'Gib')),
        ];

        yield 'requested size is higher than maximum size' => [
            new ByteSize(100, 'GiB'),
            ConstraintExceeded::diskStorageSizeRange(SpaceId::fromString(self::SPACE_ID2), new ByteSize(100, 'GiB'), new ByteSize(9, 'GiB'), new ByteSize(79, 'Gib')),
        ];

        yield 'requested size is same as current' => [
            new ByteSize(9, 'GiB'),
            null,
        ];

        yield 'requested size is within acceptable bounds' => [
            new ByteSize(15, 'GiB'),
            null,
        ];
    }

    /**
     * @test
     *
     * @doesNotPerformAssertions
     */
    public function disk_resize_without_limit(): void
    {
        $spaceId = SpaceId::fromString(self::SPACE_ID2);

        $space = $this->spaceRepository->get($spaceId);
        $space->assignCustomConstraints(
            $space->constraints->setStorageSize(ByteSize::inf())
        );
        $space->setWebQuota(new ByteSize(100, 'GiB'));
        $this->spaceRepository->save($space);

        $this->storageUsageRetriever->setDiskUsageOf($spaceId, new ByteSize(100, 'GiB'));

        $this->constraintChecker->allowHostingSize($spaceId, new ByteSize(100, 'GiB'));
        $this->constraintChecker->allowHostingSize($spaceId, new ByteSize(200, 'GiB'));
        $this->constraintChecker->allowHostingSize($spaceId, new ByteSize(200000, 'GiB'));
    }
}
