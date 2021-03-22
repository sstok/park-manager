<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Service;

use ArrayIterator;
use ParkManager\Application\Service\CombinedResultSet;
use ParkManager\Domain\ResultSet;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 */
final class CombinedResultSetTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_works_with_empty_sets(): void
    {
        $resultSetCombination = (new CombinedResultSet());

        self::assertEquals(0, $resultSetCombination->getNbResults());
        self::assertEquals([], \iterator_to_array($resultSetCombination->getIterator()));
    }

    /** @test */
    public function it_delegates_settings_to_sets(): void
    {
        $resultSetCombination = new CombinedResultSet(
            $this->getResultSetMock(new ArrayIterator()),
            $this->getResultSetMock(new ArrayIterator())
        );

        self::assertEquals([], \iterator_to_array($resultSetCombination->getIterator()));

        $resultSetCombination = (new CombinedResultSet(
            $this->getResultSetMock(new ArrayIterator(), limit: [1, 2], ordering: ['id', 'ASC'], ids: ['2', '5', '10']),
            $this->getResultSetMock(new ArrayIterator(), limit: [1, 2], ordering: ['id', 'ASC'], ids: ['2', '5', '10']),
        ))
            ->setLimit(1, 2)
            ->setOrdering('id', 'ASC')
            ->limitToIds(['2', '5', '10']);

        self::assertEquals([], \iterator_to_array($resultSetCombination->getIterator()));
    }

    private function getResultSetMock(ArrayIterator $iterator, array $limit = [null, null], array $ordering = [null, null], ?array $ids = null): ResultSet
    {
        $resultSetProphecy1 = $this->prophesize(ResultSet::class);
        $resultSetProphecy1->setLimit(...$limit)->willReturn($resultSetProphecy1)->shouldBeCalled();
        $resultSetProphecy1->setOrdering(...$ordering)->willReturn($resultSetProphecy1)->shouldBeCalled();
        $resultSetProphecy1->limitToIds($ids)->willReturn($resultSetProphecy1)->shouldBeCalled();
        $resultSetProphecy1->getIterator()->willReturn($iterator);
        $resultSetProphecy1->getNbResults()->willReturn(\count($iterator));

        return $resultSetProphecy1->reveal();
    }

    /** @test */
    public function it_combines_all_results(): void
    {
        $resultSetCombination = (new CombinedResultSet(
            $this->getResultSetMock(new ArrayIterator(['1', '5', 'aa'])),
            $this->getResultSetMock(new ArrayIterator(['9', '7', 'ddd'])),
            $this->getResultSetMock(new ArrayIterator()),
        ));

        self::assertEquals(6, $resultSetCombination->getNbResults());
        self::assertEquals(['1', '5', 'aa', '9', '7', 'ddd'], \iterator_to_array($resultSetCombination->getIterator()));
    }

    /**
     * @test
     * @dataProvider provideChangingSettingsMethods
     */
    public function it_resets_internal_iterator_when_settings_change(string $method, array $arguments): void
    {
        $resultSetProphecy = $this->prophesize(ResultSet::class);
        // Init
        $resultSetProphecy->setLimit(null, null)->willReturn($resultSetProphecy)->shouldBeCalled();
        $resultSetProphecy->setOrdering(null, null)->willReturn($resultSetProphecy)->shouldBeCalled();
        $resultSetProphecy->limitToIds(null)->willReturn($resultSetProphecy)->shouldBeCalled();
        // Not changing
        $resultSetProphecy->getIterator()->willReturn(new ArrayIterator());
        $resultSetProphecy->getNbResults()->willReturn(0);

        // Expected change
        $resultSetProphecy->{$method}(...$arguments)->willReturn($resultSetProphecy)->shouldBeCalled();

        $resultSetCombination = (new CombinedResultSet(
            $resultSetProphecy->reveal(),
        ));

        self::assertEquals([], \iterator_to_array($resultSetCombination->getIterator()));

        // Change the setting
        $resultSetCombination->{$method}(...$arguments);

        self::assertEquals([], \iterator_to_array($resultSetCombination->getIterator()));
    }

    public function provideChangingSettingsMethods(): iterable
    {
        yield 'setLimit' => ['setLimit', [1, 5]];
        yield 'setOrdering' => ['setOrdering', ['id', 'ASC']];
        yield 'limitToIds' => ['limitToIds', [['1', '2', '5']]];
    }
}
