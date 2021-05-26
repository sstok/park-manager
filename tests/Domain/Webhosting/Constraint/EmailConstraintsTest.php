<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\Constraint;

use ParkManager\Domain\ByteSize;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\EmailConstraints;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class EmailConstraintsTest extends TestCase
{
    /** @test */
    public function it_constructable(): void
    {
        $constraints = new EmailConstraints([
            'maxStorageSize' => new ByteSize(22, 'GB'),
            'maximumMailboxCount' => 10,
            'maximumForwardCount' => 20,
            'maximumAddressCount' => 60,
            'spamFilterCount' => 15,
            'mailListCount' => 8,
        ]);

        self::assertSame([], $constraints->changes);
        self::assertEquals(new ByteSize(22, 'GB'), $constraints->maxStorageSize);
        self::assertSame(10, $constraints->maximumMailboxCount);
        self::assertSame(20, $constraints->maximumForwardCount);
        self::assertSame(60, $constraints->maximumAddressCount);
        self::assertSame(15, $constraints->spamFilterCount);
        self::assertSame(8, $constraints->mailListCount);
    }

    /** @test */
    public function its_equatable(): void
    {
        $constraints = (new EmailConstraints())
            ->setMaxStorageSize(new ByteSize(12, 'GB'))
        ;

        $constraints2 = new EmailConstraints([
            'maxStorageSize' => new ByteSize(12, 'GB'),
        ]);

        self::assertTrue($constraints->equals($constraints));
        self::assertTrue($constraints->equals($constraints2));
        self::assertTrue($constraints->equals(clone $constraints));

        self::assertFalse($constraints->equals($constraints->setMaxStorageSize(new ByteSize(22, 'GB'))));
        self::assertFalse($constraints->equals($constraints->setMaximumMailboxCount(10)));
        self::assertFalse($constraints->equals($constraints->setMaximumForwardCount(20)));
        self::assertFalse($constraints->equals($constraints->setMaximumAddressCount(60)));
        self::assertFalse($constraints->equals($constraints->setSpamFilterCount(15)));
        self::assertFalse($constraints->equals($constraints->setMailListCount(8)));
    }

    /**
     * @test
     * @dataProvider provideFields
     */
    public function its_changeable(string $field, $value): void
    {
        $constraints = new EmailConstraints();

        /** @var Constraints $new */
        $new = $constraints->{'set' . ucfirst($field)}($constraints->{$field});

        self::assertSame($constraints, $new);
        self::assertSame([], $new->changes);
        self::assertSame($constraints->{$field}, $new->{$field});

        if (\is_object($value)) {
            self::assertSame($constraints, $new);
        }

        /** @var Constraints $new */
        $new = $constraints->{'set' . ucfirst($field)}($value);

        self::assertNotSame($constraints, $new);
        self::assertSame([$field => $constraints->{$field}], $new->changes);
        self::assertSame($value, $new->{$field});
    }

    public function provideFields(): iterable
    {
        yield ['maxStorageSize', new ByteSize(22, 'GB')];

        yield ['maximumMailboxCount', 10];

        yield ['maximumForwardCount', 20];

        yield ['maximumAddressCount', 60];

        yield ['spamFilterCount', 15];

        yield ['mailListCount', 8];
    }
}
