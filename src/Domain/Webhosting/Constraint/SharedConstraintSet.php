<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Constraint;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="constraints_set")
 */
class SharedConstraintSet
{
    /**
     * @ORM\Id
     * @ORM\Column(type="park_manager_webhosting_constraints_set_id")
     * @ORM\GeneratedValue(strategy="NONE")
     *
     * @var ConstraintSetId
     */
    public $id;

    /**
     * @ORM\Embedded(class=Constraints::class, columnPrefix="constraint_")
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

    public function __construct(ConstraintSetId $id, Constraints $constraints)
    {
        $this->id = $id;
        $this->constraints = $constraints;
    }

    public function getId(): ConstraintSetId
    {
        return $this->id;
    }

    public function getConstraints(): Constraints
    {
        return $this->constraints;
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

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
