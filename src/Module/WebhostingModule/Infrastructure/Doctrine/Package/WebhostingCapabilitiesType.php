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

namespace ParkManager\Module\WebhostingModule\Infrastructure\Doctrine\Package;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use ParkManager\Module\WebhostingModule\Domain\Package\Capabilities;
use ParkManager\Module\WebhostingModule\Infrastructure\Service\Package\CapabilitiesFactory;

final class WebhostingCapabilitiesType extends JsonType
{
    /**
     * @var CapabilitiesFactory|null
     */
    private $capabilitiesFactory;

    public function setCapabilitiesFactory(?CapabilitiesFactory $capabilitiesFactory): void
    {
        $this->capabilitiesFactory = $capabilitiesFactory;
    }

    /**
     * @param Capabilities|null $value
     * @param AbstractPlatform  $platform
     *
     * @return null|string
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof Capabilities) {
            throw new \InvalidArgumentException('Expected Capabilities instance.');
        }

        return parent::convertToDatabaseValue($value->toIndexedArray(), $platform);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): Capabilities
    {
        $val = parent::convertToPHPValue($value, $platform) ?? [];

        if (!isset($this->capabilitiesFactory)) {
            throw new \RuntimeException('setCapabilitiesFactory() needs to be called before this type can be used.');
        }

        return $this->capabilitiesFactory->reconstituteFromStorage($val);
    }

    public function getName(): string
    {
        return 'webhosting_capabilities';
    }
}
