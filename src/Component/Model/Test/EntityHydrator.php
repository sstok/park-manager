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

namespace ParkManager\Component\Model\Test;

/**
 * The EntityHydrator helps with testing entities that perform
 * some internal logic after the hydration process.
 *
 * The Doctrine Hydration process uses reflection
 * to initialize a new object instance and set the properties
 * values. Without using the original constructor.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class EntityHydrator
{
    private $reflection;
    private $instance;

    private function __construct(string $class)
    {
        $this->reflection = new \ReflectionClass($class);
        $this->instance = $this->reflection->newInstanceWithoutConstructor();
    }

    public static function hydrateEntity(string $class): self
    {
        return new self($class);
    }

    public function set(string $name, $value): self
    {
        $property = $this->reflection->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($this->instance, $value);

        return $this;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->instance;
    }
}
