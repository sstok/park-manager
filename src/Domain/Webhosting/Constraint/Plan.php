<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Constraint;

use Doctrine\ORM\Mapping as ORM;
use ParkManager\Domain\TimestampableTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="plan")
 */
class Plan
{
    use TimestampableTrait;

    /**
     * READ-ONLY.
     *
     * @ORM\Id
     * @ORM\Column(type="park_manager_webhosting_plan_id")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public PlanId $id;

    /**
     * READ-ONLY.
     *
     * @ORM\Embedded(class=Constraints::class, columnPrefix="constraint_")
     */
    public Constraints $constraints;

    /**
     * READ-ONLY.
     *
     * @ORM\Column(name="metadata", type="json")
     */
    public array $metadata = [];

    public function __construct(PlanId $id, Constraints $constraints)
    {
        $this->id = $id;
        $this->constraints = $constraints;
    }

    public function changeConstraints(Constraints $constraints): void
    {
        if ($this->constraints->equals($constraints)) {
            return;
        }

        $this->constraints = $constraints;
    }

    /**
     * Set some (scalar) metadata information.
     *
     * This information should only contain informational values
     * (eg. the label, description, etc).
     *
     * Not something that be used as a Domain policy. either,
     * don't use this for pricing or storing usage limitations.
     */
    public function withMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getLabel(string $locale = null): string
    {
        return $this->id->toString();
    }
}
