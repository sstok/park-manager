<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain;

use Closure;
use InvalidArgumentException;
use PHPUnit\Framework\Assert;
use RuntimeException;
use Throwable;

/**
 * Helps to quickly set-up an in-memory repository.
 *
 * @template T of object
 */
trait MockRepository
{
    /** @var array<string, T> */
    protected array $storedById = [];

    /** @var array<string, T> */
    protected array $savedById = [];

    /** @var array<string, T> */
    protected array $removedById = [];

    /**
     * Counter of saved entities (in total).
     */
    protected int $mockWasSaved = 0;

    /**
     * Count of removed entities (in total).
     */
    protected int $mockWasRemoved = 0;

    /** @var array<string, array<string, T>> [mapping-name][index-key] => {entity} */
    protected array $storedByField = [];

    /** @var array<string, array<string, array<int, T>>> [mapping-name][index-key] => {entity} */
    protected array $storedMultiByField = [];

    /** @var array<string, array<int, array{0: int, 1: Closure}>> */
    protected array $watchers = [];

    /** @var array<string, int> */
    protected array $watcherPositions = [];

    /**
     * @param array<array-key, T> $initialEntities Array of initial entities (these are not counted as saved)
     */
    public function __construct(array $initialEntities = [])
    {
        foreach ($initialEntities as $entity) {
            $this->setInMockedStorage($entity);
        }
    }

    /**
     * @param T $entity
     */
    private function setInMockedStorage(object $entity): void
    {
        $this->storedById[$this->getIdValue($entity)] = $entity;

        $indexMapping = $this->getFieldsIndexMapping();

        foreach ($indexMapping as $mapping => $getter) {
            $withGetter = $this->getValueWithGetter($entity, $getter);
            $this->storedByField[$mapping][$withGetter] = $entity;
        }

        foreach ($this->getFieldsIndexMultiMapping() as $mapping => $getter) {
            if (isset($indexMapping[$mapping])) {
                throw new RuntimeException(sprintf('Multi-mapping name "%s" already exists in single mapping.', $mapping));
            }

            $withGetter = $this->getValueWithGetter($entity, $getter);
            $this->storedMultiByField[$mapping][$withGetter][] = $entity;
        }
    }

    /**
     * @param T $entity
     */
    private function getIdValue(object $entity): string
    {
        return (string) ($this->getValueWithGetter($entity, 'id'));
    }

    private function getValueWithGetter(object $object, string | Closure $getter): mixed
    {
        if ($getter instanceof Closure) {
            return $getter($object);
        }

        if (str_starts_with($getter, '#')) {
            return $object->{mb_substr($getter, 1)};
        }

        return match (true) {
            method_exists($object, $getter) => $object->{$getter}(),
            method_exists($object, 'get' . ucfirst($getter)) => $object->{'get' . ucfirst($getter)}(),
            property_exists($object, $getter) => $object->{$getter},
            default => throw new InvalidArgumentException(
                sprintf(
                    'Unable to get field value for "%s" with getter "%s", neither "%2$s()", "get%3$s()" or property "%2$s" exists.',
                    $object::class,
                    $getter,
                    ucfirst($getter)
                )
            ),
        };
    }

    /**
     * Returns a list fields (#property, method-name or Closure for extracting)
     * to use for mapping the entity in storage.
     *
     * @return array<string, Closure|string> [mapping-name] => '#property or method'
     */
    protected function getFieldsIndexMapping(): array
    {
        return [];
    }

    /**
     * Returns a list fields (#property, method-name or Closure for extracting)
     * to use for mapping the entity in storage.
     *
     * @return array<string, Closure|string> [mapping-name] => '#property or method'
     */
    protected function getFieldsIndexMultiMapping(): array
    {
        return [];
    }

    /**
     * @param T $entity
     */
    protected function mockDoSave(object $entity): void
    {
        $this->setInMockedStorage($entity);
        $this->savedById[$id = $this->getIdValue($entity)] = $entity;
        ++$this->mockWasSaved;

        if (isset($this->watchers[$id])) {
            $watcher = array_pop($this->watchers[$id]);

            if ($watcher !== null) {
                $watcher[1]($entity);
            }
        }
    }

    /**
     * @param T $entity
     */
    protected function mockDoRemove(object $entity): void
    {
        $this->removedById[$this->getIdValue($entity)] = $entity;
        ++$this->mockWasRemoved;
    }

    /**
     * @return T
     */
    protected function mockDoGetById(string | object | int $id): object
    {
        $idStr = (string) $id;

        if (! isset($this->storedById[$idStr])) {
            $this->throwOnNotFound($id);
        }

        $this->guardNotRemoved($id);

        return $this->storedById[$idStr];
    }

    protected function guardNotRemoved(mixed $id): void
    {
        $idStr = (string) $id;

        if (isset($this->removedById[$idStr])) {
            $this->throwOnNotFound($id);
        }
    }

    /**
     * @return T
     */
    protected function mockDoGetByField(string $key, float | int | string | null $value): object
    {
        if (! isset($this->storedByField[$key][$value])) {
            $this->throwOnNotFound($value);
        }

        $entity = $this->storedByField[$key][$value];
        $this->guardNotRemoved($this->getValueWithGetter($entity, 'id'));

        return $entity;
    }

    /**
     * @return MockRepoResultSet<T>
     */
    protected function mockDoGetMultiByField(string $key, float | int | string | null $value): MockRepoResultSet
    {
        if (! isset($this->storedMultiByField[$key][$value])) {
            return new MockRepoResultSet([]);
        }

        if (\count($this->removedById) > 0) {
            /** @var array<int, T> $entities */
            $entities = [];

            foreach ($this->storedMultiByField[$key][$value] as $entity) {
                if (isset($this->removedById[$this->getIdValue($entity)])) {
                    continue;
                }

                $entities[] = $entity;
            }

            return new MockRepoResultSet($entities);
        }

        return new MockRepoResultSet($this->storedMultiByField[$key][$value]);
    }

    /**
     * @return MockRepoResultSet<T>
     */
    protected function mockDoGetAll(): MockRepoResultSet
    {
        if (! \count($this->storedById)) {
            return new MockRepoResultSet([]);
        }

        if (\count($this->removedById) > 0) {
            $entities = [];

            foreach ($this->storedById as $entity) {
                if (isset($this->removedById[$this->getIdValue($entity)])) {
                    continue;
                }

                $entities[] = $entity;
            }

            return new MockRepoResultSet($entities);
        }

        return new MockRepoResultSet($this->storedById);
    }

    /**
     * @param Closure(T): bool $condition
     *
     * @return MockRepoResultSet<T>
     */
    protected function mockDoGetMultiByCondition(Closure $condition): MockRepoResultSet
    {
        if (! \count($this->storedById)) {
            return new MockRepoResultSet([]);
        }

        $entities = [];

        foreach ($this->storedById as $entity) {
            if (isset($this->removedById[$this->getIdValue($entity)])) {
                continue;
            }

            if ($condition($entity)) {
                $entities[] = $entity;
            }
        }

        return new MockRepoResultSet($entities);
    }

    protected function mockDoHasByField(string $key, float | int | string | null $value): bool
    {
        if (! isset($this->storedByField[$key][$value])) {
            return false;
        }

        return ! isset($this->removedById[$this->getValueWithGetter($this->storedByField[$key][$value], 'id')->toString()]);
    }

    /**
     * @throws Throwable
     */
    abstract protected function throwOnNotFound(mixed $key): void;

    public function assertNoEntitiesWereSaved(): void
    {
        Assert::assertEquals(0, $this->mockWasSaved, 'No entities were expected to be stored');
    }

    public function resetRecordingState(): void
    {
        $this->mockWasSaved = 0;
        $this->mockWasRemoved = 0;
    }

    /**
     * @param array<int, T> $entities
     */
    public function assertEntitiesWereSaved(array $entities = []): void
    {
        Assert::assertGreaterThan(0, $this->mockWasSaved, 'Entities were expected to be stored');

        if ($entities) {
            Assert::assertEquals($entities, array_values($this->savedById));
        }
    }

    /**
     * @param Closure(T): bool $expected
     */
    public function assertEntityWasSavedThat(string | int | object $id, Closure $expected): void
    {
        Assert::assertGreaterThan(0, $this->mockWasSaved, 'Entities were expected to be stored');

        $key = (string) $id;
        Assert::assertArrayHasKey($key, $this->savedById);

        if ($expected($this->savedById[$key])) {
            $this->guardNotRemoved($this->getValueWithGetter($this->savedById[$key], 'id'));

            return;
        }

        Assert::fail('No entity was found (by saving) that gave a Closure condition.');
    }

    /**
     * Adds a 'watcher' that is executed when an entity with this $id is saved,
     * at the expected position (before actually saving!).
     *
     * When no explicit position is given the last position is used.
     * This method is best used with assertions that check if the entity was actually saved.
     *
     * @param Closure(T): void $excepted
     */
    public function whenEntityIsSavedAt(string | int | object $id, Closure $excepted, ?int $position = null): void
    {
        $id = (string) $id;

        if (! isset($this->watchers[$id])) {
            $this->watchers[$id] = [];
            $this->watcherPositions[$id] = -1;
        }

        ++$this->watcherPositions[$id];
        $this->watchers[$id][] = [$position ?? $this->watcherPositions[$id], $excepted];

        // Sort in reverse order, the last item will be 'popped' and executed.
        uasort($this->watchers[$id], static fn (array $a, array $b): int => $b[0] <=> $a[0]);
    }

    /**
     * @param Closure(T): bool $excepted
     */
    public function assertEntitiesWereSavedThat(Closure $excepted): void
    {
        Assert::assertGreaterThan(0, $this->mockWasSaved, 'Entities were expected to be stored');

        foreach ($this->savedById as $entity) {
            if ($excepted($entity)) {
                $this->guardNotRemoved($this->getValueWithGetter($entity, 'id'));

                return;
            }
        }

        Assert::fail('No entity was found (by saving) that gave a Closure condition.');
    }

    public function assertEntitiesCountWasSaved(int $count): void
    {
        Assert::assertEquals($count, $this->mockWasSaved, 'Entities were expected to be stored');
    }

    public function assertNoEntitiesWereRemoved(): void
    {
        if ($this->mockWasRemoved > 0) {
            Assert::fail(sprintf('No entities were expected to be removed, but %d entities were removed.', $this->mockWasSaved));
        }
    }

    /**
     * @param array<int, (T|scalar)> $entities
     */
    public function assertEntitiesWereRemoved(array $entities): void
    {
        Assert::assertGreaterThan(0, $this->mockWasRemoved, 'No entities were removed');

        if (is_scalar(reset($entities))) {
            Assert::assertEquals($entities, array_keys($this->removedById));
        } else {
            Assert::assertEquals($entities, array_values($this->removedById));
        }
    }

    /**
     * @phpstan-param Closure(T): (void|bool) $excepted
     */
    public function assertHasEntity(string | int | object $id, Closure $excepted): void
    {
        $key = (string) $id;
        Assert::assertArrayHasKey($key, $this->storedById);

        $result = $excepted($this->storedById[$key]);

        if (\is_bool($result)) {
            Assert::assertTrue($result, sprintf('Expected that an entity with "%s" exists that passes the Closure expectation.', $key));
        }

        $this->guardNotRemoved($this->getValueWithGetter($this->storedById[$key], 'id'));
    }

    /**
     * @param Closure(T): bool $excepted
     */
    public function assertHasEntityThat(Closure $excepted): void
    {
        foreach ($this->storedById as $entity) {
            if ($excepted($entity)) {
                $this->guardNotRemoved($this->getValueWithGetter($entity, 'id'));

                return;
            }
        }

        Assert::fail('No entity was found that gave a Closure condition.');
    }
}
