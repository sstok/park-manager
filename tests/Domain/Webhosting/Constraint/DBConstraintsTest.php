<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\Constraint;

use Lifthill\Component\Common\Domain\Model\ByteSize;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\DBConstraints;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DBConstraintsTest extends TestCase
{
    /** @test */
    public function it_constructable(): void
    {
        $constraints = new DBConstraints([
            'providedStorageSize' => new ByteSize(200, 'MiB'),
            'maximumAmountPerType' => 10,
            'enabledPgsql' => true,
            'enabledMysql' => false,
        ]);

        self::assertSame([], $constraints->changes);
        self::assertEquals(new ByteSize(200, 'MiB'), $constraints->providedStorageSize);
        self::assertSame(10, $constraints->maximumAmountPerType);
        self::assertTrue($constraints->enabledPgsql);
        self::assertFalse($constraints->enabledMysql);
    }

    /** @test */
    public function its_equatable(): void
    {
        $constraints = (new DBConstraints())
            ->setProvidedStorageSize(new ByteSize(12, 'GB'));

        $constraints2 = new DBConstraints([
            'providedStorageSize' => new ByteSize(12, 'GB'),
        ]);

        self::assertTrue($constraints->equals($constraints));
        self::assertTrue($constraints->equals($constraints2));
        self::assertTrue($constraints->equals(clone $constraints));

        self::assertFalse($constraints->equals($constraints->setProvidedStorageSize(new ByteSize(22, 'GB'))));
        self::assertFalse($constraints->equals($constraints->setMaximumAmountPerType(10)));
        self::assertFalse($constraints->equals($constraints->disablePgsql()));
        self::assertFalse($constraints->equals($constraints->disableMysql()));
    }

    /**
     * @test
     *
     * @dataProvider provideIts_changeableCases
     */
    public function its_changeable(string $field, mixed $value): void
    {
        $constraints = new DBConstraints();

        if (\is_bool($value)) {
            /** @var Constraints $new */
            $new = $constraints->{'disable' . ucfirst($field)}();
        } else {
            /** @var Constraints $new */
            $new = $constraints->{'set' . ucfirst($field)}($value);
        }

        if (\is_bool($value)) {
            $field = 'enabled' . ucfirst($field);
        }

        self::assertNotSame($constraints, $new);
        self::assertSame([$field => $constraints->{$field}], $new->changes);
        self::assertSame($value, $new->{$field});
        self::assertNotSame($constraints->{$field}, $new->{$field});
    }

    /**
     * @return \Generator<int, array{0: string, 1: mixed}>
     */
    public static function provideIts_changeableCases(): iterable
    {
        yield ['providedStorageSize', new ByteSize(22, 'GB')];

        yield ['maximumAmountPerType', 20];

        yield ['pgsql', false];

        yield ['mysql', false];
    }
}
