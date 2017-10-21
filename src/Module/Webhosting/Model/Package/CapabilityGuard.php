<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\Webhosting\Model\Package;

use ParkManager\Component\Model\LogMessage\LogMessages;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccount;

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
 *
 * When a Capability has more then one attribute it's recommend to "log" a
 * message to inform the UI layer about the specific reason.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
interface CapabilityGuard
{
    /**
     * @param Capability        $configuration Current Capability configuration
     * @param array             $context       Additional information about the operation
     *                                         (implement dependent - not required)
     * @param WebhostingAccount $account
     * @param LogMessages       $messages      The LogMessages allows to "log" messages for
     *                                         extra information about the failure or a warning
     *                                         about reaching the limits of the account's capabilities
     *
     * @return bool
     */
    public function isAllowed(Capability $configuration, array $context, WebhostingAccount $account, LogMessages $messages): bool;
}
