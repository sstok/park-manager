<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Infrastructure\ServiceBus\Middleware;

use League\Tactician\Middleware;
use ParkManager\Component\ApplicationFoundation\Message\ServiceMessages;
use ParkManager\Module\WebhostingModule\Application\AccountIdAwareCommand;
use ParkManager\Module\WebhostingModule\Infrastructure\Service\Package\CapabilitiesRestrictionGuard;

/**
 * The AccountCapabilitiesRestrictionGuardMiddleware ensures that a Command
 * does not violate the Account Capabilities Restriction.
 *
 * Note: This Middleware should be registered *after* the AccessGuard.
 */
final class AccountCapabilitiesRestrictionGuardMiddleware implements Middleware
{
    private $accountCapabilitiesGuard;
    private $serviceMessages;

    public function __construct(CapabilitiesRestrictionGuard $capabilitiesGuard, ServiceMessages $serviceMessages)
    {
        $this->accountCapabilitiesGuard = $capabilitiesGuard;
        $this->serviceMessages          = $serviceMessages;
    }

    public function execute($command, callable $next)
    {
        if ($command instanceof AccountIdAwareCommand && ! $this->accountCapabilitiesGuard->decide($command, $this->serviceMessages)) {
            return false;
        }

        return $next($command);
    }
}
