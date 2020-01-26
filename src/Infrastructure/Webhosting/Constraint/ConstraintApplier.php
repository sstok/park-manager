<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Webhosting\Constraint;

use ParkManager\Domain\Webhosting\Account\WebhostingAccount;
use ParkManager\Domain\Webhosting\Plan\Constraint;

/**
 * A ConstraintApplier applies the constraint's configuration
 * on the given webhosting account.
 *
 * This sub-system should only be used when the constraint is enforced
 * outside of the webhosting system (like a filesystem quota).
 */
interface ConstraintApplier
{
    public function apply(Constraint $configuration, WebhostingAccount $account, array $context = []): void;
}
