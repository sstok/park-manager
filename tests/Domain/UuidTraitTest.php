<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain;

use JsonSerializable;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\UuidTrait;
use PHPUnit\Framework\TestCase;
use Serializable;
use stdClass;

/**
 * @internal
 */
final class UuidTraitTest extends TestCase
{
    /** @test */
    public function it_allows_creating_new_instance(): void
    {
        $id = MockUuidIdentity::create();

        self::assertInstanceOf(MockUuidIdentity::class, $id);
    }

    /** @test */
    public function it_allows_comparing(): void
    {
        $id = MockUuidIdentity::create();
        $id2 = MockUuidIdentity::create();

        self::assertTrue($id->equals($id));
        self::assertFalse($id->equals($id2));
        self::assertFalse($id->equals(false));
        self::assertFalse($id->equals(null));

        $id = MockUuidIdentity::fromString('56253090-3960-11e7-94fd-acbc32b58315');

        self::assertTrue($id->equals($id));
        self::assertFalse($id->equals($id2));
        self::assertFalse($id->equals(false));
    }

    /** @test */
    public function it_allows_static_comparing(): void
    {
        $id = MockUuidIdentity::create();
        $id2 = MockUuidIdentity::create();

        self::assertTrue(MockUuidIdentity::equalsValue($id, $id));
        self::assertTrue(MockUuidIdentity::equalsValue(null, null));
        self::assertTrue(MockUuidIdentity::equalsValue($id, MockUuidIdentity::fromString($id->toString())));
        self::assertTrue(MockUuidIdentity::equalsValue(MockUuidIdentity::fromString($id->toString()), $id));
        self::assertFalse(MockUuidIdentity::equalsValue($id, $id2));
        self::assertFalse(MockUuidIdentity::equalsValue($id, null));
        self::assertFalse(MockUuidIdentity::equalsValue(null, $id));
        self::assertFalse(MockUuidIdentity::equalsValue(null, new stdClass()));
        self::assertFalse(MockUuidIdentity::equalsValue($id, UserId::fromString($id->toString())));
        self::assertFalse(MockUuidIdentity::equalsValue(UserId::fromString($id->toString()), $id));
    }

    /** @test */
    public function it_allows_comparing_by_entity_field(): void
    {
        $id = MockUuidIdentity::create();
        $id2 = MockUuidIdentity2::create();

        $entity1 = new MockIdentityEntity($id);
        $entity2 = new MockIdentityEntity($id, $id2);

        self::assertTrue(MockUuidIdentity2::equalsValueOfEntity(null, null, 'child'), 'Both null should be equal');
        self::assertTrue(MockUuidIdentity2::equalsValueOfEntity($id2, $entity2, 'child'), 'Should be equal if entity field equals the provided identity');
        self::assertTrue(MockUuidIdentity2::equalsValueOfEntity(MockUuidIdentity2::fromString($id2->toString()), $entity2, 'child'), 'Should be equal if entity field equals the provided identity');

        self::assertFalse(MockUuidIdentity2::equalsValueOfEntity(null, $entity1, 'child'), 'Should not be equal when identity is null');
        self::assertFalse(MockUuidIdentity2::equalsValueOfEntity($id2, null, 'child'), 'Should not be equal when entity is null');
        self::assertFalse(MockUuidIdentity2::equalsValueOfEntity(null, $entity2, 'child'), 'Should not be equal when identity is null');
        self::assertFalse(MockUuidIdentity2::equalsValueOfEntity($id2, $entity1, 'child'), 'Should not be equal when entity field is null');
        self::assertFalse(MockUuidIdentity::equalsValueOfEntity($id, $entity2, 'child'), 'Should not be equal when identity is not of same type');
    }

    /** @test */
    public function it_can_be_cast_to_string(): void
    {
        $id = MockUuidIdentity::fromString('56253090-3960-11e7-94fd-acbc32b58315');

        self::assertEquals('56253090-3960-11e7-94fd-acbc32b58315', (string) $id);
    }

    /** @test */
    public function its_serializable(): void
    {
        $id = MockUuidIdentity::fromString('56253090-3960-11e7-94fd-acbc32b58315');
        $serialized = \serialize($id);

        self::assertEquals($id, \unserialize($serialized, []));
    }

    /** @test */
    public function its_json_serializable(): void
    {
        $id = MockUuidIdentity::fromString('56253090-3960-11e7-94fd-acbc32b58315');
        $serialized = \json_encode($id, \JSON_THROW_ON_ERROR, 512);

        self::assertEquals('56253090-3960-11e7-94fd-acbc32b58315', \json_decode($serialized, true, 512, \JSON_THROW_ON_ERROR));
    }
}

/** @internal */
final class MockUuidIdentity implements Serializable, JsonSerializable
{
    use UuidTrait;
}

/** @internal */
final class MockUuidIdentity2 implements Serializable, JsonSerializable
{
    use UuidTrait;
}

/** @internal */
final class MockIdentityEntity
{
    public MockUuidIdentity $id;
    public ?MockUuidIdentity2 $child;

    public function __construct(MockUuidIdentity $id, ?MockUuidIdentity2 $childId = null)
    {
        $this->id = $id;
        $this->child = $childId;
    }
}
