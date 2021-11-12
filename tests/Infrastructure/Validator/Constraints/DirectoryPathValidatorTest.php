<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Validator\Constraints;

use Generator;
use ParkManager\Infrastructure\Validator\Constraints\DirectoryPath;
use ParkManager\Infrastructure\Validator\Constraints\DirectoryPathValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @internal
 */
final class DirectoryPathValidatorTest extends ConstraintValidatorTestCase
{
    /** @test */
    public function it_fails_with_path_to_long(): void
    {
        $constraint = new DirectoryPath();

        $this->validator->validate($v = str_repeat('A', 3097), $constraint);

        $this->buildViolation($constraint->pathToLongMessage)
            ->setInvalidValue($v)
            ->setCode(DirectoryPath::PATH_TO_LONG_ERROR)
            ->setParameter('{{ max }}', '3096')
            ->assertRaised()
        ;
    }

    /** @test */
    public function it_fails_with_chunk_to_long(): void
    {
        $v = str_repeat('A', 256);
        $constraint = new DirectoryPath();

        $this->validator->validate('testing-deep/' . $v, $constraint);

        $this->buildViolation($constraint->nameToLongMessage)
            ->setInvalidValue($v)
            ->setCode(DirectoryPath::NAME_TO_LONG_ERROR)
            ->setParameter('{{ value }}', '"testing-deep/' . $v . '"')
            ->setParameter('{{ chunk }}', '"' . $v . '"')
            ->setParameter('{{ max }}', '255')
            ->assertRaised()
        ;
    }

    /**
     * @test
     * @dataProvider provideInvalidValues
     */
    public function it_fails_with_invalid_path(string $value, ?string $chunk = null): void
    {
        $constraint = new DirectoryPath();

        $this->validator->validate($value, $constraint);
        $chunk ??= $value;

        $this->buildViolation($constraint->message)
            ->setInvalidValue($value)
            ->setCode(DirectoryPath::INVALID_PATH)
            ->setParameter('{{ value }}', '"' . $value . '"')
            ->setParameter('{{ chunk }}', '"' . $chunk . '"')
            ->assertRaised()
        ;
    }

    /**
     * @return Generator<int, array<int, string>>
     */
    public function provideInvalidValues(): Generator
    {
        yield ['@'];
        yield ['%2ef'];
        yield ["\x2e"]; // "." (period)
        yield ["\x5C"]; // "\" (backslash)
        yield ["\xB7"];
        yield ['.'];
        yield [','];
        yield [':'];
        yield [';'];
        yield ['!'];
        yield ['#'];
        yield ['$'];
        yield ['%'];
        yield ['^'];
        yield ['&'];
        yield ['*'];
        yield ['('];
        yield [')'];
        yield [')'];
        yield ['+'];

        // Traversing
        yield ['/', ''];
        yield ['/.bar/./', '.'];
        yield ['\\'];
        yield ['../', '..'];
        yield ['..', '..'];
        yield ['/./', '.'];
        yield ['//', ''];
        yield ['\\.\\./', '\.\.'];
        yield ['bar/../', '..'];
        yield ["j\xE2e"]; // "/" (Division Slash)

        yield ['bar/@', '@'];
        yield ['bar/l@', 'l@'];
        yield ['bar/l@@l', 'l@@l'];
        yield ['bar/l@.l', 'l@.l'];
        yield ['bar/l--l', 'l--l'];
        yield ['bar/.', '.'];
        yield ['bar/@.', '@.'];
        yield ['bar/l.@', 'l.@'];
        yield ['bar/lal.', 'lal.'];
        yield ['bar/l..l', 'l..l'];
        yield ['bar/car/<he>/he', '<he>'];
        yield ['bar/car/{nope}', '{nope}'];
        yield ['bar/car/\\sfs', '\\sfs'];
        yield ["bar/car/he\0he/now", "he\0he"]; // NULL char
    }

    /**
     * @test
     * @dataProvider provideValidValues
     */
    public function it_accepts_valid_path(?string $value): void
    {
        $constraint = new DirectoryPath();

        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @return Generator<int, array{0: string|null}>
     */
    public function provideValidValues(): Generator
    {
        yield [null];
        yield ['l'];
        yield ['l@l'];
        yield ['public'];
        yield ['public/'];
        yield ['/public/'];
        yield ['/pub-6@6-lic/'];
        yield ['klsjflsfsjjslkfsjs/fsjfsjfsjfsjfkls/sfsjfsfjsfjslfk/fsifjsisjfjsfjsl'];
        yield ['blog@1.0.0.0/public'];
        yield ['blog/v1.0.0.0/public'];
        yield ['blog/v1.4320/public'];
        yield ['.git/heads'];
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new DirectoryPathValidator();
    }
}
