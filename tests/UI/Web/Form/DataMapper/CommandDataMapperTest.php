<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form\DataMapper;

use ParkManager\UI\Web\Form\DataMapper\CommandDataMapper;
use ParkManager\UI\Web\Form\DataMapper\PropertyPathObjectAccessor;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
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
    public function it_only_accepts_array_as_data_to_form($input): void
    {
        $dataMapper = new CommandDataMapper($this->createMock(DataMapperInterface::class), new PropertyPathObjectAccessor());

        $this->expectExceptionObject(new UnexpectedTypeException($input, 'array with keys "model" and "fields"'));

        $dataMapper->mapDataToForms($input, []);
    }

    public function provide_invalid_input(): iterable
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
    public function it_only_accepts_array_as_forms_to_data($input): void
    {
        $dataMapper = new CommandDataMapper($this->createMock(DataMapperInterface::class), new PropertyPathObjectAccessor());

        $this->expectExceptionObject(new UnexpectedTypeException($input, 'array with keys "model" and "fields"'));

        $dataMapper->mapFormsToData([], $input);
    }

    /**
     * @test
     */
    public function it_maps_data(): void
    {
        $fields = ['field1' => true];
        $data = ['id' => 1];

        $wrappedDataMapperProphecy = $this->prophesize(DataMapperInterface::class);
        $wrappedDataMapperProphecy->mapDataToForms($data, [null, 2])->shouldBeCalled();
        $wrappedDataMapperProphecy->mapFormsToData([null], $fields)->shouldNotBeCalled();

        $form = $this->factory->createBuilder()->getForm();

        $dataMapper = new CommandDataMapper($wrappedDataMapperProphecy->reveal(), new PropertyPathObjectAccessor());

        $dataMapper->mapDataToForms(['model' => $data, 'fields' => $fields], [null, 2]);

        $viewData = ['model' => $data, 'fields' => $fields];
        $dataMapper->mapFormsToData([$form], $viewData);
    }
}
