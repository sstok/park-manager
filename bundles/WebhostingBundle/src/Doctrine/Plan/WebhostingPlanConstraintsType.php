<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Doctrine\Plan;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use InvalidArgumentException;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraints;
use ParkManager\Bundle\WebhostingBundle\Plan\ConstraintsFactory;
use RuntimeException;

final class WebhostingPlanConstraintsType extends JsonType
{
    /** @var ConstraintsFactory|null */
    private $constraintsFactory;

    public function setConstraintsFactory(?ConstraintsFactory $constraintsFactory): void
    {
        $this->constraintsFactory = $constraintsFactory;
    }

    /**
     * @param Constraints|null $value
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof Constraints) {
            throw new InvalidArgumentException('Expected Constraints instance.');
        }

        return parent::convertToDatabaseValue($value->toIndexedArray(), $platform);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): Constraints
    {
        $val = parent::convertToPHPValue($value, $platform) ?? [];

        if (! isset($this->constraintsFactory)) {
            throw new RuntimeException('setConstraintsFactory() needs to be called before this type can be used.');
        }

        return $this->constraintsFactory->reconstituteFromStorage($val);
    }

    public function getName(): string
    {
        return 'webhosting_plan_constraints';
    }
}
