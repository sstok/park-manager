<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Constraint;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Locale;
use ParkManager\Domain\TimestampableTrait;

#[Entity]
#[Table(name: 'plan')]
class Plan
{
    use TimestampableTrait;

    /**
     * @param array<string, string> $labels
     * @param array<string, mixed>  $metadata
     */
    public function __construct(
        #[Id]
        #[Column(type: 'park_manager_webhosting_plan_id')]
        #[GeneratedValue(strategy: 'NONE')]
        public PlanId $id,

        #[ORM\Embedded(class: Constraints::class, columnPrefix: 'constraint_')]
        public Constraints $constraints,

        #[Column(name: 'labels', type: 'json')]
        public array $labels = [],

        #[Column(name: 'metadata', type: 'json')]
        public array $metadata = []
    ) {
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
     * (eg. the description, etc).
     *
     * Not something that be used as a Domain policy. either,
     * don't use this for pricing or storing usage limitations.
     *
     * @param array<string, mixed> $metadata
     */
    public function withMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * @param @param array<string, string> $labels
     */
    public function withLabels(array $labels): void
    {
        $this->labels = $labels;
    }

    public function getLabel(?string $locale = null): string
    {
        return $this->labels[$locale ?? Locale::getDefault()] ?? $this->labels['_default'] ?? $this->id->toString();
    }
}
