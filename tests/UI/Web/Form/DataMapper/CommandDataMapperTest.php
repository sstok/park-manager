<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form\DataMapper;

use ArrayIterator;
use Generator;
use ParkManager\UI\Web\Form\DataMapper\CommandDataMapper;
use ParkManager\UI\Web\Form\DataMapper\PropertyPathObjectAccessor;
use ParkManager\UI\Web\Form\Model\CommandDto;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

/**
 * @internal
 */
final class CommandDataMapperTest extends FormIntegrationTestCase
{
    use ProphecyTrait;

    /**
     * @test
     * @dataProvider provide_invalid_input
     */
    public function it_only_accepts_command_dto_as_data_to_form(mixed $input): void
    {
        $dataMapper = new CommandDataMapper($this->createMock(DataMapperInterface::class), new PropertyPathObjectAccessor());

        $this->expectExceptionObject(new UnexpectedTypeException($input, CommandDto::class));

        $dataMapper->mapDataToForms($input, new ArrayIterator([]));
    }

    /**
     * @return Generator<int, array<int, mixed>>
     */
    public function provide_invalid_input(): Generator
    {
        yield [null];

        yield [[]];

        yield [['models']];

        yield [['fields']];
    }

    /**
     * @test
     * @dataProvider provide_invalid_input
     */
    public function it_only_accepts_command_dto_as_forms_to_data(mixed $input): void
    {
        $dataMapper = new CommandDataMapper($this->createMock(DataMapperInterface::class), new PropertyPathObjectAccessor());

        $this->expectExceptionObject(new UnexpectedTypeException($input, CommandDto::class));

        $dataMapper->mapFormsToData(new ArrayIterator([]), $input);
    }

    /**
     * @test
     */
    public function it_maps_data(): void
    {
        $fields = ['field1' => true];
        $data = ['id' => 1];

        $forms = [$this->createMock(FormInterface::class), $this->createMock(FormInterface::class)];

        $wrappedDataMapperProphecy = $this->prophesize(DataMapperInterface::class);
        $wrappedDataMapperProphecy->mapDataToForms($data, new ArrayIterator($forms))->shouldBeCalled();
        $wrappedDataMapperProphecy->mapFormsToData(new ArrayIterator([null]), $fields)->shouldNotBeCalled();

        $form = $this->factory->createBuilder()->getForm();

        $dataMapper = new CommandDataMapper($wrappedDataMapperProphecy->reveal(), new PropertyPathObjectAccessor());

        $viewData = new CommandDto(model: $data, fields: $fields);

        $dataMapper->mapDataToForms($viewData, new ArrayIterator($forms));
        $dataMapper->mapFormsToData(new ArrayIterator([$form]), $viewData);
    }
}
