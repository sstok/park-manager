<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * An Identity holds a single UUID value.
 *
 * Use this trait in any ValueObject that uniquely identifies an Entity.
 */
trait UuidTrait
{
    private UuidInterface $value;
    private string $stringValue;

    private function __construct(UuidInterface $value)
    {
        $this->value = $value;
        $this->stringValue = $value->toString();
    }

    public static function create(): static
    {
        return new static(Uuid::uuid4());
    }

    public static function fromString(string $value): static
    {
        return new static(Uuid::fromString($value));
    }

    public function __toString(): string
    {
        return $this->stringValue;
    }

    public function toString(): string
    {
        return $this->stringValue;
    }

    /**
     * @param mixed|static $identity
     */
    public function equals($identity): bool
    {
        if (! $identity instanceof self) {
            return false;
        }

        return $this->value->equals($identity->value);
    }

    /**
     * Allows to easily compare the equality of an identity.
     *
     * NOTE: This will only return true if both identities
     * are of "this" instance type. Or both are null.
     *
     * @param object|static|null $identity1
     * @param object|static|null $identity2
     */
    public static function equalsValue($identity1, $identity2): bool
    {
        if ($identity1 === null && $identity2 === null) {
            return true;
        }

        return $identity1 instanceof static && $identity1->equals($identity2);
    }

    /**
     * Allows to compare the public property (holding the actual identity) of an entity
     * against the given identity object.
     *
     * NOTE: This will only return true if both identities
     * are of "this" instance type. Or both are null.
     *
     * @param object|static|null $identity Identity (of this instance) object or null
     * @param object|null        $entity   Entity object to extract the property from or null
     * @param string             $property The property-name of $entity to get the identity VO
     */
    public static function equalsValueOfEntity($identity, $entity, string $property): bool
    {
        if ($entity === null && $identity === null) {
            return true;
        }

        if ($entity === null) {
            return false;
        }

        $entityId = $entity->{$property};

        return $entityId instanceof static && $entityId->equals($identity);
    }

    public function serialize(): string
    {
        return $this->stringValue;
    }

    public function unserialize($serialized): void
    {
        $this->value = Uuid::fromString($serialized);
        $this->stringValue = $this->value->toString();
    }

    public function jsonSerialize(): string
    {
        return $this->stringValue;
    }
}
