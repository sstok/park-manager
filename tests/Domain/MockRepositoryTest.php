<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain;

use Closure;
use InvalidArgumentException;
use ParkManager\Domain\ResultSet;
use ParkManager\Domain\UniqueIdentity;
use ParkManager\Domain\UuidTrait;
use ParkManager\Tests\Mock\Domain\MockRepository;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class MockRepositoryTest extends TestCase
{
    /** @test */
    public function it_has_no_entities_saved_or_removed(): void
    {
        $repository = new class() {
            /** @use MockRepository<MockEntity> */
            use MockRepository;

            protected function throwOnNotFound(mixed $key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }
        };

        $repository->assertNoEntitiesWereSaved();
        $repository->assertNoEntitiesWereRemoved();
    }

    /** @test */
    public function it_gets_entity(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315');
        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315');

        $repository = new class([$entity1, $entity2]) {
            /** @use MockRepository<MockEntity> */
            use MockRepository;

            protected function throwOnNotFound(mixed $key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            public function get(MockIdentity $id): MockEntity
            {
                return $this->mockDoGetById($id);
            }
        };

        $repository->assertNoEntitiesWereSaved();
        $repository->assertNoEntitiesWereRemoved();
        $repository->assertHasEntity($entity1->id(), static function (): void { });
        $repository->assertHasEntity($entity2->id(), static function (): void { });
        self::assertSame($entity1, $repository->get($entity1->id()));
        self::assertSame($entity2, $repository->get($entity2->id()));
    }

    /** @test */
    public function it_gets_multiple_entities(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315', 'bla', 'example.com');
        $entity2 = new MockEntity('d7b8386b-ac16-49c1-9257-0bf047337e6f', 'bla', 'example.com');
        $entity3 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315', 'barfoo', 'example2.com');
        $entity4 = new MockEntity('566eb8e3-d9ba-4d6d-8d3c-c4a744df85ae', 'foobar', 'example2.com');
        $entity5 = new MockEntity('f1acc3fb-de6a-4fc4-af6e-dde2327b4425', 'foobar', 'example2.com');

        $repository = new class([$entity1, $entity2, $entity3, $entity4, $entity5]) {
            /** @use MockRepository<MockEntity> */
            use MockRepository;

            protected function throwOnNotFound(mixed $key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            public function get(MockIdentity $id): MockEntity
            {
                return $this->mockDoGetById($id);
            }

            /**
             * @return ResultSet<MockEntity>
             */
            public function allByDomain(string $key): ResultSet
            {
                return $this->mockDoGetMultiByField('domain', $key);
            }

            /**
             * @return ResultSet<MockEntity>
             */
            public function all(): ResultSet
            {
                return $this->mockDoGetAll();
            }

            public function remove(MockEntity $entity): void
            {
                $this->mockDoRemove($entity);
            }

            /**
             * @return array<string, string|Closure>
             */
            protected function getFieldsIndexMultiMapping(): array
            {
                return [
                    'domain' => 'getDomain',
                ];
            }
        };

        $repository->remove($entity5);

        $repository->assertHasEntity($entity1->id(), static function (): void { });
        $repository->assertHasEntity($entity3->id(), static function (): void { });

        self::assertSame([$entity1, $entity2], [...$repository->allByDomain('example.com')]);
        self::assertSame([$entity3, $entity4], [...$repository->allByDomain('example2.com')]);
        self::assertSame([$entity1, $entity2, $entity3, $entity4], [...$repository->all()]);

        self::assertSame([$entity3], [...$repository->allByDomain('example2.com')->limitToIds(['9dab0b6a-0876-11e9-bfd1-acbc32b58315'])]);
        self::assertSame([], [...$repository->allByDomain('example2.com')->limitToIds(['f1acc3fb-de6a-4fc4-af6e-dde2327b4425'])]);

        self::assertSame([$entity1, $entity2], [...$repository->all()->setLimit(2)]);
        self::assertSame([$entity3, $entity4], [...$repository->all()->setLimit(2, 2)]);
        self::assertSame([$entity3, $entity4], [...$repository->all()->setLimit(null, 2)]);

        self::assertSame([$entity1, $entity2, $entity3, $entity4], [...$repository->all()->setOrdering('id', 'desc')]);
        self::assertSame([$entity4, $entity3, $entity2, $entity1], [...$repository->all()->setOrdering('id', 'asc')]);
    }

    /** @test */
    public function it_gets_entity_by_field_method(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315', 'John');
        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315', 'Jane');

        $repository = new class([$entity1, $entity2]) {
            /** @use MockRepository<MockEntity> */
            use MockRepository;

            protected function throwOnNotFound(mixed $key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            /**
             * @return array<string, string>
             */
            protected function getFieldsIndexMapping(): array
            {
                return ['last_name' => 'lastName'];
            }

            public function getByLastName(string $name): MockEntity
            {
                return $this->mockDoGetByField('last_name', $name);
            }
        };

        self::assertSame($entity1, $repository->getByLastName('John'));
        self::assertSame($entity2, $repository->getByLastName('Jane'));
    }

    /** @test */
    public function it_gets_entity_by_field_property(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315');
        $entity1->name = 'John';

        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315');
        $entity2->name = 'Jane';

        $repository = new class([$entity1, $entity2]) {
            /** @use MockRepository<MockEntity> */
            use MockRepository;

            protected function throwOnNotFound(mixed $key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            /**
             * @return array<string, string>
             */
            protected function getFieldsIndexMapping(): array
            {
                return ['Name' => '#name'];
            }

            public function getByName(string $name): MockEntity
            {
                return $this->mockDoGetByField('Name', $name);
            }
        };

        self::assertSame($entity1, $repository->getByName('John'));
        self::assertSame($entity2, $repository->getByName('Jane'));
    }

    /** @test */
    public function it_gets_entity_by_field_closure(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315', 'John');
        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315', 'Jane');

        $repository = new class([$entity1, $entity2]) {
            /** @use MockRepository<MockEntity> */
            use MockRepository;

            protected function throwOnNotFound(mixed $key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            /**
             * @return array<string, string|Closure>
             */
            protected function getFieldsIndexMapping(): array
            {
                return ['last_name' => static fn (MockEntity $entity): string => mb_strtolower($entity->lastName())];
            }

            public function getByLastName(string $name): MockEntity
            {
                return $this->mockDoGetByField('last_name', $name);
            }
        };

        self::assertSame($entity1, $repository->getByLastName('john'));
        self::assertSame($entity2, $repository->getByLastName('jane'));
    }

    /** @test */
    public function it_saves_entity(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315');
        $entity1->name = 'John';

        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315');
        $entity2->name = 'Jane';

        $repository = new class([$entity1, $entity2]) {
            /** @use MockRepository<MockEntity> */
            use MockRepository;

            protected function throwOnNotFound(mixed $key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            /**
             * @return array<string, string|Closure>
             */
            protected function getFieldsIndexMapping(): array
            {
                return ['Name' => '#name'];
            }

            public function getByName(string $name): MockEntity
            {
                return $this->mockDoGetByField('Name', $name);
            }

            public function save(MockEntity $entity): void
            {
                $this->mockDoSave($entity);
            }
        };

        $entity1->name = 'Jones';

        $repository->save($entity1);

        $repository->assertEntitiesWereSaved();
        $repository->assertNoEntitiesWereRemoved();
        $repository->assertEntitiesWereSavedThat(static fn (MockEntity $savedEntity): bool => $savedEntity->name === 'Jones' || $savedEntity->name === 'Jane');
        $repository->assertEntityWasSavedThat($entity1->id, static fn (MockEntity $savedEntity): bool => $savedEntity->name === 'Jones');

        self::assertSame($entity1, $repository->getByName('Jones'));
        self::assertSame($entity2, $repository->getByName('Jane'));
    }

    /** @test */
    public function it_removes_entity(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315');
        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315');

        $repository = new class([$entity1, $entity2]) {
            /** @use MockRepository<MockEntity> */
            use MockRepository;

            protected function throwOnNotFound(mixed $key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            public function get(MockIdentity $id): MockEntity
            {
                return $this->mockDoGetById($id);
            }

            public function remove(MockEntity $entity): void
            {
                $this->mockDoRemove($entity);
            }
        };

        $repository->remove($entity1);
        $repository->assertNoEntitiesWereSaved();

        $repository->assertEntitiesWereRemoved([$entity1]);
        $repository->assertHasEntity($entity2->id(), static function (): void { });
        self::assertSame($entity2, $repository->get($entity2->id()));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No, I has not have that key: ' . $entity1->id());

        $repository->get($entity1->id());
    }

    /** @test */
    public function it_executes_watchers_in_correct_order(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315', 'John');
        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315', 'Jane');

        $repository = new class([$entity1, $entity2]) {
            /** @use MockRepository<MockEntity> */
            use MockRepository;

            protected function throwOnNotFound(mixed $key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            public function save(MockEntity $entity): void
            {
                $this->mockDoSave($entity);
            }
        };

        $order = [];
        $order2 = [];

        $repository->whenEntityIsSavedAt('fc86687e-0875-11e9-9701-acbc32b58315', static function (MockEntity $entity1) use (&$order): void {
            $order[] = 'he1';
        }, 0);
        $repository->whenEntityIsSavedAt('fc86687e-0875-11e9-9701-acbc32b58315', static function (MockEntity $entity2) use (&$order): void {
            $order[] = 'now2';
        }, 1);
        $repository->whenEntityIsSavedAt('fc86687e-0875-11e9-9701-acbc32b58315', static function (MockEntity $entity3) use (&$order): void {
            $order[] = 'this4';
        }, 4);
        $repository->whenEntityIsSavedAt('fc86687e-0875-11e9-9701-acbc32b58315', static function (MockEntity $entity4) use (&$order): void {
            $order[] = 'sing3';
        } /* 3 */);

        $repository->whenEntityIsSavedAt('9dab0b6a-0876-11e9-bfd1-acbc32b58315', static function (MockEntity $entity4) use (&$order2): void {
            $order2[] = 'its me';
        }, 2);
        $repository->whenEntityIsSavedAt('9dab0b6a-0876-11e9-bfd1-acbc32b58315', static function (MockEntity $entity4) use (&$order2): void {
            $order2[] = 'hello';
        }, 1);

        $repository->save($entity1);
        $repository->save($entity2);
        $repository->save($entity1);
        $repository->save($entity1);
        $repository->save($entity1);
        $repository->save($entity1); // No more watchers at this point
        $repository->save($entity2);

        self::assertSame(['he1', 'now2', 'sing3', 'this4'], $order);
        self::assertSame(['hello', 'its me'], $order2);

        $order = [];

        $repository->whenEntityIsSavedAt('fc86687e-0875-11e9-9701-acbc32b58315', static function (MockEntity $entity4) use (&$order): void {
            $order[] = 'corrosion4';
        }, 1);

        $repository->whenEntityIsSavedAt('fc86687e-0875-11e9-9701-acbc32b58315', static function (MockEntity $entity4) use (&$order): void {
            $order[] = 'on me5'; // Not executed.
        }, 2);

        // The old watchers should now all be popped.
        // Meaning all new watchers should be executed in correct order.
        $repository->save($entity1);

        self::assertSame(['corrosion4'], $order);
    }
}

/** @internal */
final class MockIdentity implements UniqueIdentity
{
    use UuidTrait;
}

/** @internal */
final class MockEntity
{
    public MockIdentity $id;
    public ?string $name = null;
    private string $lastName;
    private ?string $domain;

    public function __construct(string $id = 'fc86687e-0875-11e9-9701-acbc32b58315', string $name = 'Foobar', ?string $domain = null)
    {
        $this->id = MockIdentity::fromString($id);
        $this->lastName = $name;
        $this->domain = $domain;
    }

    public function id(): MockIdentity
    {
        return $this->id;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }
}
