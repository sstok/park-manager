<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Tests\Model;

use InvalidArgumentException;
use ParkManager\Bundle\CoreBundle\Test\Model\MockRepository;
use ParkManager\Bundle\CoreBundle\Tests\Model\Mock\EmailChanged;
use ParkManager\Bundle\CoreBundle\Tests\Model\Mock\MockEntity;
use ParkManager\Bundle\CoreBundle\Tests\Model\Mock\MockIdentity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class MockRepositoryTest extends TestCase
{
    /** @test */
    public function it_has_no_enties_saved_or_removed(): void
    {
        $repository = new class() {
            use MockRepository;

            protected function throwOnNotFound($key): void
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
            use MockRepository;

            protected function throwOnNotFound($key): void
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
        static::assertSame($entity1, $repository->get($entity1->id()));
        static::assertSame($entity2, $repository->get($entity2->id()));
    }

    /** @test */
    public function it_gets_entity_by_field_method(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315', 'John');
        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315', 'Jane');

        $repository = new class([$entity1, $entity2]) {
            use MockRepository;

            protected function throwOnNotFound($key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            protected function getFieldsIndexMapping(): array
            {
                return ['last_name' => 'lastName'];
            }

            public function getByLastName(string $name): MockEntity
            {
                return $this->mockDoGetByField('last_name', $name);
            }
        };

        static::assertSame($entity1, $repository->getByLastName('John'));
        static::assertSame($entity2, $repository->getByLastName('Jane'));
    }

    /** @test */
    public function it_gets_entity_by_field_property(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315');
        $entity1->name = 'John';

        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315');
        $entity2->name = 'Jane';

        $repository = new class([$entity1, $entity2]) {
            use MockRepository;

            protected function throwOnNotFound($key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            protected function getFieldsIndexMapping(): array
            {
                return ['Name' => '#name'];
            }

            public function getByName(string $name): MockEntity
            {
                return $this->mockDoGetByField('Name', $name);
            }
        };

        static::assertSame($entity1, $repository->getByName('John'));
        static::assertSame($entity2, $repository->getByName('Jane'));
    }

    /** @test */
    public function it_gets_entity_by_field_closure(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315', 'John');
        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315', 'Jane');

        $repository = new class([$entity1, $entity2]) {
            use MockRepository;

            protected function throwOnNotFound($key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            protected function getFieldsIndexMapping(): array
            {
                return ['last_name' => static function (MockEntity $entity) { return \mb_strtolower($entity->lastName()); }];
            }

            public function getByLastName(string $name): MockEntity
            {
                return $this->mockDoGetByField('last_name', $name);
            }
        };

        static::assertSame($entity1, $repository->getByLastName('john'));
        static::assertSame($entity2, $repository->getByLastName('jane'));
    }

    /** @test */
    public function it_gets_entity_by_event(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315', 'John');
        $entity1->changeEmail('John@example.com');

        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315', 'Jane');
        $entity2->changeEmail('Jane@example.com');

        $repository = new class([$entity1, $entity2]) {
            use MockRepository;

            protected function throwOnNotFound($key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            protected function getEventsIndexMapping(): array
            {
                return [EmailChanged::class => 'email'];
            }

            public function getByEmail(string $email): MockEntity
            {
                return $this->mockDoGetByEvent(EmailChanged::class, $email);
            }
        };

        static::assertSame($entity1, $repository->getByEmail('John@example.com'));
        static::assertSame($entity2, $repository->getByEmail('Jane@example.com'));
    }

    /** @test */
    public function it_gets_entity_by_event_with_multiple_fired(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315', 'John');
        $entity1->changeEmail('John@example.com');
        $entity1->changeEmail('John2@example.com');

        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315', 'Jane');
        $entity2->changeEmail('Jane@example.com');

        $repository = new class([$entity1, $entity2]) {
            use MockRepository;

            protected function throwOnNotFound($key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

            protected function getEventsIndexMapping(): array
            {
                return [EmailChanged::class => 'email'];
            }

            public function getByEmail(string $email): MockEntity
            {
                return $this->mockDoGetByEvent(EmailChanged::class, $email);
            }
        };

        static::assertSame($entity1, $repository->getByEmail('John2@example.com'));
        static::assertSame($entity2, $repository->getByEmail('Jane@example.com'));
    }

    /** @test */
    public function it_saves_entity(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315');
        $entity1->name = 'John';

        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315');
        $entity2->name = 'Jane';

        $repository = new class([$entity1, $entity2]) {
            use MockRepository;

            protected function throwOnNotFound($key): void
            {
                throw new InvalidArgumentException('No, I has not have that key: ' . $key);
            }

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
        static::assertSame($entity1, $repository->getByName('Jones'));
        static::assertSame($entity2, $repository->getByName('Jane'));
    }

    /** @test */
    public function it_removes_entity(): void
    {
        $entity1 = new MockEntity('fc86687e-0875-11e9-9701-acbc32b58315');
        $entity2 = new MockEntity('9dab0b6a-0876-11e9-bfd1-acbc32b58315');

        $repository = new class([$entity1, $entity2]) {
            use MockRepository;

            protected function throwOnNotFound($key): void
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
        static::assertSame($entity2, $repository->get($entity2->id()));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No, I has not have that key: ' . $entity1->id());

        $repository->get($entity1->id());
    }
}
