<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form\Type;

use Generator;
use ParkManager\Domain\ByteSize;
use ParkManager\UI\Web\Form\Type\ByteSizeType;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * @internal
 */
final class ByteSizeTypeTest extends TypeTestCase
{
    /** @test */
    public function its_constructable_with_empty(): void
    {
        $form = $this->factory->create(ByteSizeType::class);

        self::assertNull($form->getData());
    }

    /** @test */
    public function its_submittable_with_empty(): void
    {
        $form = $this->factory->create(ByteSizeType::class);
        $form->submit([]);

        self::assertNull($form->getData());
    }

    /** @test */
    public function it_rejects_fraction_for_byte_unit(): void
    {
        $form = $this->factory->create(ByteSizeType::class);
        $form->submit(['value' => 10.10, 'unit' => 'byte']);

        self::assertFalse($form->isValid());
        self::assertStringContainsString('Fractions are not accepted for Byte unit.', $form->getTransformationFailure()->getMessage());
    }

    /**
     * @test
     * @dataProvider provideModelFormats
     */
    public function it_transforms_from_model_format(ByteSize $input): void
    {
        $form = $this->factory->create(ByteSizeType::class, $input, ['infinite_replacement' => new ByteSize(10.10, 'gib')]);

        self::assertEquals($input, $form->getData());
    }

    /**
     * @return Generator<int, array{0: ByteSize}>
     */
    public function provideModelFormats(): Generator
    {
        yield [new ByteSize(10, 'byte')];
        yield [new ByteSize(10.10, 'kib')];
        yield [new ByteSize(10.10, 'mib')];
        yield [new ByteSize(10.10, 'gib')];
        yield [ByteSize::inf()];
    }

    /** @test */
    public function it_transforms_from_model_format_with_inf_replacement(): void
    {
        $form = $this->factory->create(
            ByteSizeType::class,
            ByteSize::inf(),
            ['allow_infinite' => false, 'infinite_replacement' => $replacement = new ByteSize(10.10, 'gib')]
        );

        self::assertSame($replacement, $form->getData());
    }

    /**
     * @test
     * @dataProvider provideInputsFormats
     *
     * @param array{value: float, unit: string} $input
     */
    public function it_transforms_from_view_format(ByteSize $expected, array $input): void
    {
        $form = $this->factory->create(ByteSizeType::class);
        $form->submit($input);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());
        self::assertEquals($expected, $form->getData());
    }

    /**
     * @return Generator<int, array{0: ByteSize, 1: array{value: float, unit: string}}>
     */
    public function provideInputsFormats(): Generator
    {
        yield [new ByteSize(10, 'byte'), ['value' => 10, 'unit' => 'byte']];
        yield [new ByteSize(10, 'byte'), ['value' => 10.00, 'unit' => 'byte']]; // While fractions are not accepted, using 0 is accepted.
        yield [new ByteSize(10.10, 'kib'), ['value' => 10.10, 'unit' => 'kib']];
        yield [new ByteSize(10.10, 'mib'), ['value' => 10.10, 'unit' => 'mib']];
        yield [new ByteSize(10.10, 'gib'), ['value' => 10.10, 'unit' => 'gib']];
        yield [ByteSize::inf(), ['isInf' => '1', 'value' => 10.10, 'unit' => 'byte']];
    }
}
