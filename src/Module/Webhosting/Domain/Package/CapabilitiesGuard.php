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

namespace ParkManager\Module\Webhosting\Domain\Package;

use ParkManager\Component\Model\LogMessage\LogMessages;
use ParkManager\Module\Webhosting\Domain\Account\WebhostingAccountId;

/**
 * The CapabilitiesGuard checks if one ore capabilities can be safely
 * performed on the WebhostingAccount.
 *
 * The CapabilitiesGuard receives the `WebhostingAccountId`,
 * fetches the account from the Domain Repository.
 *
 * The fetched WebhostingAccount has a Capabilities set,
 * the CapabilitiesGuard gets the related Webhosting Capability
 * by `$capabilityName`. And executes the related CapabilityGuard.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
interface CapabilitiesGuard
{
    /**
     * Checks whether the webhosting account (will) exceed(s)
     * the package limitations for the given capabilities.
     *
     * @param WebhostingAccountId $accountId
     * @param array               $context           Additional information about the operation
     * @param string              ...$capabilityName One or more capability names
     *                                               to extract from the webhosting account
     *
     * @return LogMessages
     */
    public function allowedTo(WebhostingAccountId $accountId, array $context, string ...$capabilityName): LogMessages;
}
