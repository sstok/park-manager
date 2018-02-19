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

namespace ParkManager\Module\Webhosting\Service\Package;

use ParkManager\Component\Model\LogMessage\LogMessages;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccountId;
use ParkManager\Module\Webhosting\Model\Package\CapabilitiesGuard;
use ParkManager\Module\Webhosting\Model\Package\CapabilityCoveringCommand;

/**
 * The CommandToCapabilitiesGuard maps a Command to a capability and runs
 * its Guard (using the AccountCapabilitiesGuard).
 *
 * Based on the result of all CapabilityGuards this service
 * returns whether the Command is "allowed" to pass trough.
 *
 * This service is expected to be executed from a
 * AuthorizationService Guard (of the ServiceBus).
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class CommandToCapabilitiesGuard
{
    private $accountCapabilitiesGuard;
    private $contextProvider;

    public function __construct(CapabilitiesGuard $accountCapabilitiesGuard, ?callable $contextProvider = null)
    {
        $this->accountCapabilitiesGuard = $accountCapabilitiesGuard;
        $this->contextProvider = $contextProvider;
    }

    public function commandAllowedFor(object $command, WebhostingAccountId $account): LogMessages
    {
        if (!$command instanceof CapabilityCoveringCommand) {
            return new LogMessages();
        }

        $context = null !== $this->contextProvider ? ($this->contextProvider)($command, $account) : [];

        return $this->accountCapabilitiesGuard->allowedTo($account, $context, $command::getCapability());
    }
}
