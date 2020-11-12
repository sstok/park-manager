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
use ParkManager\Domain\Webhosting\Constraint\DBConstraints;
use ParkManager\Domain\Webhosting\Constraint\EmailConstraints;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ConstraintsTest extends TestCase
{
    /** @test */
    public function it_constructable(): void
    {
        $constraints = new Constraints([
            'monthlyTraffic' => 50,
            'storageSize' => new ByteSize(22, 'GB'),
            'email' => new EmailConstraints(['maximumAddressCount' => 50]),
            'database' => new DBConstraints(['maximumAmountPerType' => 5]),
        ]);

        self::assertEquals([], $constraints->changes);
        self::assertEquals(50, $constraints->monthlyTraffic);
        self::assertEquals(new ByteSize(22, 'GB'), $constraints->storageSize);
        self::assertEquals(new EmailConstraints(['maximumAddressCount' => 50]), $constraints->email);
        self::assertEquals(new DBConstraints(['maximumAmountPerType' => 5]), $constraints->database);
    }

    /** @test */
    public function its_equatable(): void
    {
        $constraints = (new Constraints())
            ->setStorageSize(new ByteSize(12, 'GB'))
            ->setMonthlyTraffic(10);

        $constraints2 = new Constraints([
            'storageSize' => new ByteSize(12, 'GB'),
            'monthlyTraffic' => 10,
        ]);

        self::assertTrue($constraints->equals($constraints));
        self::assertTrue($constraints->equals($constraints2));
        self::assertTrue($constraints->equals(clone $constraints));

        self::assertFalse($constraints->equals($constraints->setStorageSize(new ByteSize(22, 'GB'))));
        self::assertFalse($constraints->equals($constraints->setMonthlyTraffic(60)));
        self::assertFalse($constraints->equals($constraints->setEmail((new EmailConstraints())->setMailListCount(10))));
    }

    /**
     * @test
     * @dataProvider provideFields
     */
    public function its_changeable(string $field, $value): void
    {
        $constraints = new Constraints();

        /** @var Constraints $new */
        $new = $constraints->{'set' . \ucfirst($field)}($constraints->{$field});

        self::assertSame($constraints, $new);
        self::assertEquals([], $new->changes);
        self::assertEquals($constraints->{$field}, $new->{$field});

        if (\is_object($value)) {
            self::assertSame($constraints, $new);
        }

        /** @var Constraints $new */
        $new = $constraints->{'set' . \ucfirst($field)}($value);

        self::assertNotSame($constraints, $new);
        self::assertEquals([$field => $constraints->{$field}], $new->changes);
        self::assertEquals($value, $new->{$field});
    }

    public function provideFields(): iterable
    {
        yield ['monthlyTraffic', 50];

        yield ['storageSize', new ByteSize(22, 'GB')];

        yield ['email', (new EmailConstraints())->setMaximumAddressCount(50)];

        yield ['database', (new DBConstraints())->setMaximumAmountPerType(50)];
    }
}
