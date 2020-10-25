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
 * Use this trait any in ValueObject that uniquely identifies an Entity.
 */
trait UuidTrait
{
    private UuidInterface $value;
    private string $stringValue;

    protected function __construct(UuidInterface $value)
    {
        $this->value = $value;
        $this->stringValue = $value->toString();
    }

    /**
     * @return static
     */
    public static function create()
    {
        return new static(Uuid::uuid4());
    }

    /**
     * @return static
     */
    public static function fromString(string $value)
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
     * @param static|mixed $identity
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
     * @param static|object|null $identity1
     * @param static|object|null $identity2
     * @param string|null        $property  Given $identity1 is an Entity class this will use
     *                                      the property of the entity to get the identity VO
     */
    public static function equalsValue($identity1, $identity2, ?string $property = null): bool
    {
        if ($identity1 === null && $identity2 === null) {
            return true;
        }

        if ($identity1 !== null && $property !== null) {
            $identity1 = $identity1->{$property};
        }

        if (! $identity1 instanceof static || ! $identity2 instanceof static) {
            return false;
        }

        return $identity1->equals($identity2);
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
