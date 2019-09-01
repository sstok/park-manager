<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Package;

use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccount;
use ParkManager\Bundle\WebhostingBundle\Model\Package\Capability;

/**
 * A CapabilityGuard ensures the performed operation doesn't violate
 * the Capability's limitation.
 *
 * For example if a webhosting account only allows 10 mailboxes
 * the guard must check if the current amount of mailboxes (within the account)
 * does not exceed this limit.
 *
 * Caution: An account's Capabilities can be updated any moment, so when
 * the account already has 10 mailboxes and the Capability was updated
 * to only allow 8 the guard still MUST return false.
 */
interface CapabilityGuard
{
    /**
     * @param Capability      $configuration Current Capability configuration
     * @param array           $context       Additional information about the operation
     *                                       (implement dependent - not required)
     */
    public function decide(Capability $configuration, array $context, WebhostingAccount $account): bool;
}