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
    public PlanId $id;
    public Constraints $constraints;
    public ?array $metadata;
    public bool $updateLinkedSpaces;

    public function __construct(PlanId $id, Constraints $constraints, ?array $metadata, bool $updateLinkedSpaces = false)
    {
        $this->id = $id;
        $this->constraints = $constraints;
        $this->metadata = $metadata;
        $this->updateLinkedSpaces = $updateLinkedSpaces;
    }
}
