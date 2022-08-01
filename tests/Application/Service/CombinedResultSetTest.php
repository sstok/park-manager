<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Service;

use ArrayIterator;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Expression;
use Generator;
use ParkManager\Application\Service\CombinedResultSet;
use ParkManager\Domain\ResultSet;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
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

        self::assertSame(0, $resultSetCombination->getNbResults());
        self::assertSame([], iterator_to_array($resultSetCombination->getIterator()));
    }

    /** @test */
    public function it_delegates_settings_to_sets(): void
    {
        $resultSetCombination = new CombinedResultSet(
            $this->getResultSetMock(new ArrayIterator()),
            $this->getResultSetMock(new ArrayIterator())
        );

        self::assertSame([], iterator_to_array($resultSetCombination->getIterator()));

        $resultSetCombination = (new CombinedResultSet(
            $this->getResultSetMock(new ArrayIterator(), limitAndOffset: [1, 2], ordering: ['id', 'ASC'], ids: ['2', '5', '10'], expression: $expression = new Comparison('id', Comparison::EQ, '1')),
            $this->getResultSetMock(new ArrayIterator(), limitAndOffset: [1, 2], ordering: ['id', 'ASC'], ids: ['2', '5', '10'], expression: $expression),
        ))
            ->setLimit(1, 2)
            ->setOrdering('id', 'ASC')
            ->filter($expression)
            ->limitToIds(['2', '5', '10'])
        ;

        self::assertSame([], iterator_to_array($resultSetCombination->getIterator()));
    }

    /**
     * @param ArrayIterator<int, mixed>   $iterator
     * @param array<int, int|null>        $limitAndOffset
     * @param array<int, string|null>     $ordering
     * @param array<int, string|int>|null $ids
     *
     * @return ResultSet<mixed>
     */
    private function getResultSetMock(ArrayIterator $iterator, array $limitAndOffset = [null, null], array $ordering = [null, null], ?array $ids = null, ?Expression $expression = null): ResultSet
    {
        $resultSetProphecy = $this->prophesize(ResultSet::class);
        $resultSetProphecy->setLimit(...$limitAndOffset)->willReturn($resultSetProphecy);
        $resultSetProphecy->setOrdering(...$ordering)->willReturn($resultSetProphecy);
        $resultSetProphecy->limitToIds($ids)->willReturn($resultSetProphecy);
        $resultSetProphecy->filter($expression)->willReturn($resultSetProphecy);
        $resultSetProphecy->getIterator()->willReturn($iterator);
        $resultSetProphecy->getNbResults()->willReturn(\count($iterator));

        return $resultSetProphecy->reveal();
    }

    /** @test */
    public function it_combines_all_results(): void
    {
        $resultSetCombination = (new CombinedResultSet(
            $this->getResultSetMock(new ArrayIterator(['1', '5', 'aa'])),
            $this->getResultSetMock(new ArrayIterator(['9', '7', 'ddd'])),
            $this->getResultSetMock(new ArrayIterator()),
        ));

        self::assertSame(6, $resultSetCombination->getNbResults());
        self::assertSame(['1', '5', 'aa', '9', '7', 'ddd'], iterator_to_array($resultSetCombination->getIterator()));
    }

    /**
     * @test
     * @dataProvider provideChangingSettingsMethods
     *
     * @param array<int, mixed> $arguments
     */
    public function it_resets_internal_iterator_when_settings_change(string $method, array $arguments): void
    {
        $resultSetProphecy = $this->prophesize(ResultSet::class);
        // Init
        $resultSetProphecy->setLimit(null, null)->willReturn($resultSetProphecy);
        $resultSetProphecy->setOrdering(null, null)->willReturn($resultSetProphecy);
        $resultSetProphecy->limitToIds(null)->willReturn($resultSetProphecy);
        $resultSetProphecy->filter(null)->willReturn($resultSetProphecy);
        // Not changing
        $resultSetProphecy->getIterator()->willReturn(new ArrayIterator());
        $resultSetProphecy->getNbResults()->willReturn(0);

        // Expected change
        $resultSetProphecy->{$method}(...$arguments)->willReturn($resultSetProphecy)->shouldBeCalled();

        $resultSetCombination = (new CombinedResultSet(
            $resultSetProphecy->reveal(),
        ));

        self::assertSame([], iterator_to_array($resultSetCombination->getIterator()));

        // Change the setting
        $resultSetCombination->{$method}(...$arguments);

        self::assertSame([], iterator_to_array($resultSetCombination->getIterator()));
    }

    /**
     * @return Generator<string, array<int, mixed>>
     */
    public function provideChangingSettingsMethods(): Generator
    {
        yield 'setLimit' => ['setLimit', [1, 5]];
        yield 'setOrdering' => ['setOrdering', ['id', 'ASC']];
        yield 'limitToIds' => ['limitToIds', [['1', '2', '5']]];
        yield 'filter' => ['filter', [new Comparison('id', Comparison::EQ, '1')]];
    }

    /**
     * @test
     */
    public function it_keeps_original_settings_when_not_changed(): void
    {
        $resultSetProphecy = $this->prophesize(ResultSet::class);
        $resultSetProphecy->setLimit(Argument::any(), Argument::any())->willReturn($resultSetProphecy)->shouldNotBeCalled();
        $resultSetProphecy->setOrdering(Argument::any(), Argument::any())->willReturn($resultSetProphecy)->shouldNotBeCalled();
        $resultSetProphecy->limitToIds(Argument::any())->willReturn($resultSetProphecy)->shouldNotBeCalled();
        $resultSetProphecy->filter(Argument::any())->willReturn($resultSetProphecy)->shouldNotBeCalled();
        $resultSetProphecy->getIterator()->willReturn(new ArrayIterator());
        $resultSetProphecy->getNbResults()->willReturn(0);

        $resultSetCombination = (new CombinedResultSet(
            $resultSetProphecy->reveal(),
        ));

        self::assertSame([], iterator_to_array($resultSetCombination->getIterator()));
    }
}
