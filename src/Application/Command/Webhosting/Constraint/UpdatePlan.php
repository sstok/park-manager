<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\Constraint;

use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\PlanId;

final class UpdatePlan
{
    /**
     * @param array<string, mixed>|null  $metadata metadata is only updated (in full) when this value is non-null
     * @param array<string, string>|null $labels
     */
    public function __construct(
        public PlanId $id,
        public Constraints $constraints,
        public ?array $metadata = null,
        public ?array $labels = null,
        public bool $updateLinkedSpaces = false
    ) {
        $this->withLabels($labels);
    }

    /**
     * @param array<string, string>|null $labels
     *
     * @return $this
     */
    public function withLabels(array $labels = null): static
    {
        if ($labels !== null) {
            ksort($labels);
        }

        $this->labels = $labels;

        return $this;
    }
}
