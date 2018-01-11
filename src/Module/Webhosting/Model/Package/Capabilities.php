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

namespace ParkManager\Module\Webhosting\Model\Package;

use ParkManager\Module\Webhosting\Model\Package\Exception\CapabilityNotInSet;

/**
 * Capabilities holds an immutable set of unique Capability objects.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class Capabilities implements \IteratorAggregate
{
    /**
     * @var Capability[]
     */
    private $capabilities = [];

    private $capabilitiesArray = [];
    private $capabilitiesIndexedArray = [];

    public function __construct(Capability ...$capabilities)
    {
        foreach ($capabilities as $capability) {
            $class = get_class($capability);
            $this->capabilities[$class] = $capability;
            $this->capabilitiesArray[$class] = $capability->configuration();
            $this->capabilitiesIndexedArray[$capability::id()] = $capability->configuration();
        }
    }

    public function add(Capability ...$capabilities): self
    {
        // Cannot unpack array with string keys (class uniqueness is guarded by the constructor)
        return new self(...array_merge(array_values($this->capabilities), $capabilities));
    }

    public function remove(Capability ...$capabilities): self
    {
        $capabilitiesList = $this->capabilities;

        foreach ($capabilities as $capability) {
            unset($capabilitiesList[get_class($capability)]);
        }

        return new self(...array_values($capabilitiesList));
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->capabilities);
    }

    public function has(string $capability): bool
    {
        return isset($this->capabilities[$capability]);
    }

    public function get(string $capability): Capability
    {
        if (!isset($this->capabilities[$capability])) {
            throw CapabilityNotInSet::withName($capability);
        }

        return $this->capabilities[$capability];
    }

    public function toArray(): array
    {
        return $this->capabilitiesArray;
    }

    public function toIndexedArray(): array
    {
        return $this->capabilitiesIndexedArray;
    }

    public function equals(self $other): bool
    {
        if ($this === $other) {
            return true;
        }

        // Leave values of the array are expected to be scalar. So strict comparison is possible.
        return $this->capabilitiesArray === $other->capabilitiesArray;
    }

    public static function reconstituteFromArray(array $capabilities): self
    {
        $capabilitiesInstances = [];

        foreach ($capabilities as $class => $configuration) {
            $capabilitiesInstances[] = $class::reconstituteFromArray($configuration);
        }

        return new self(...$capabilitiesInstances);
    }

    /**
     * Reconstitutes a Capabilities set from storage.
     *
     * Unlike reconstituteFromArray() this expects the capabilites
     * are provided by their id (not their name).
     *
     * @param CapabilitiesFactory $factory
     * @param array               $capabilities
     *
     * @return Capabilities
     */
    public static function reconstituteFromStorage(CapabilitiesFactory $factory, array $capabilities): self
    {
        $capabilitiesInstances = [];

        foreach ($capabilities as $id => $configuration) {
            $capabilitiesInstances[] = $factory->createById($id, $configuration);
        }

        return new self(...$capabilitiesInstances);
    }
}
