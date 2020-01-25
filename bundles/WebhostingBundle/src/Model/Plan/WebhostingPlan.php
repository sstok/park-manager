<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Model\Plan;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="plan", schema="webhosting")
 */
class WebhostingPlan
{
    /**
     * @ORM\Id
     * @ORM\Column(type="park_manager_webhosting_plan_id")
     * @ORM\GeneratedValue(strategy="NONE")
     *
     * @var WebhostingPlanId
     */
    protected $id;

    /**
     * @ORM\Column(name="constraints", type="webhosting_plan_constraints", nullable=true)
     *
     * @var Constraints
     */
    protected $constraints;

    /**
     * @ORM\Column(name="metadata", type="json")
     *
     * @var array
     */
    private $metadata = [];

    public function __construct(WebhostingPlanId $id, Constraints $constraints)
    {
        $this->id = $id;
        $this->constraints = $constraints;
    }

    public function getId(): WebhostingPlanId
    {
        return $this->id;
    }

    public function getConstraints(): Constraints
    {
        return $this->constraints;
    }

    public function changeConstraints(Constraints $constraints): void
    {
        $this->constraints = $constraints;
    }

    /**
     * Set some (scalar) metadata information for the webhosting plan.
     *
     * This information should only contain informational values
     * (eg. the label, description, etc).
     *
     * Not something that be used as a Domain policy. either,
     * don't use this for pricing or storing user-type limitations.
     */
    public function withMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
