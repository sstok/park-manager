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

namespace ParkManager\Module\Webhosting\Service\Package;

use ParkManager\Module\Webhosting\Model\Package\CapabilitiesFactory;
use ParkManager\Module\Webhosting\Model\Package\Capability;
use ParkManager\Module\Webhosting\Model\Package\CapabilityGuard;
use ParkManager\Module\Webhosting\Model\Package\ConfigurationApplier;
use ParkManager\Module\Webhosting\Model\Package\Exception\CapabilityNotRegistered;
use Psr\Container\ContainerInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class CapabilitiesRegistry implements CapabilitiesFactory
{
    private $capabilities;
    private $capabilitiesById;
    private $guardServices;
    private $applierServices;

    /**
     * @param array[]            $capabilities     Capabilities and there configuration
     * @param string[]           $capabilitiesById Map Capability ID to Capability name
     * @param ContainerInterface $guardServices    Service container for lazy loading guard services
     * @param ContainerInterface $applierServices  Service container for lazy loading applier services
     */
    public function __construct(array $capabilities, array $capabilitiesById, ContainerInterface $guardServices, ContainerInterface $applierServices)
    {
        $this->capabilities = $capabilities;
        $this->capabilitiesById = $capabilitiesById;
        $this->guardServices = $guardServices;
        $this->applierServices = $applierServices;
    }

    public function createById(string $id, array $options): Capability
    {
        if (!isset($this->capabilitiesById[$id])) {
            throw CapabilityNotRegistered::withId($id);
        }

        return $this->createByName($this->capabilitiesById[$id], $options);
    }

    public function createByName(string $capabilityName, array $options): Capability
    {
        if (!isset($this->capabilities[$capabilityName])) {
            throw CapabilityNotRegistered::withName($capabilityName);
        }

        return $capabilityName::reconstituteFromArray($options);
    }

    public function getConfig(string $capabilityName): array
    {
        if (!isset($this->capabilities[$capabilityName])) {
            throw CapabilityNotRegistered::withName($capabilityName);
        }

        return $this->capabilities[$capabilityName];
    }

    public function getGuard(string $capabilityName): CapabilityGuard
    {
        if (!isset($this->capabilities[$capabilityName])) {
            throw CapabilityNotRegistered::withName($capabilityName);
        }

        return $this->guardServices->get($this->capabilities[$capabilityName]['guard']);
    }

    public function getConfigurationApplier(string $capabilityName): ConfigurationApplier
    {
        if (!isset($this->capabilities[$capabilityName])) {
            throw CapabilityNotRegistered::withName($capabilityName);
        }

        return $this->applierServices->get($this->capabilities[$capabilityName]['applier']);
    }
}
