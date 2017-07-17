<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Component\Model\Tests;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
trait ObjectHydrationAssertTrait
{
    /**
     * Assert the object-value equals the expected (after being reconstituted/hydration).
     *
     * To be used. When the Class does special logic to construct an internal value.
     * Eg. id (string) converted to an Id object ()
     *
     * @param string      $className
     * @param mixed       $value
     * @param mixed       $expected
     * @param string      $property
     * @param null|string $method
     */
    protected static function assertHydratedObjectValueEquals(string $className, $value, $expected, string $property = 'id', ?string $method = null): void
    {
        $reflection = new \ReflectionClass($className);
        $obj = $reflection->newInstanceWithoutConstructor();

        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($obj, $value);

        if (null === $method) {
            $method = $property;
        }

        self::assertEquals($expected, $obj->{$method}()); // First run to initialize
        self::assertEquals($expected, $obj->{$method}()); // Second time (with object)
    }
}
