<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Webhosting\Constraint;

use ParkManager\Domain\Webhosting\Constraint\Constraint;
use ParkManager\Domain\Webhosting\Space\Space;

/**
 * A ConstraintApplier applies the constraint's configuration
 * on the given webhosting space.
 *
 * This sub-system should only be used when the constraint is enforced
 * outside of the webhosting system (like a filesystem quota).
 */
interface ConstraintApplier
{
    public function apply(Constraint $configuration, Space $space, array $context = []): void;
}
