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

namespace ParkManager\Module\Webhosting\ServiceBus\Package;

use League\Tactician\Middleware;
use ParkManager\Component\Model\LogMessage\LogMessages;
use ParkManager\Module\Webhosting\Model\Package\CapabilitiesGuard;
use ParkManager\Module\Webhosting\Model\Package\CapabilityCoveringCommand;

/**
 * The CapabilityCoveringCommandValidator maps a Command to a capability
 * and runs its Guard (using the CapabilitiesGuard).
 *
 * Based on the result of all CapabilityGuards this middleware
 * either continues the execution returns null (ending the handling).
 *
 * Note: This Middleware should be registered *after* the MessageGuard.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class CapabilityCoveringCommandValidator implements Middleware
{
    private $accountCapabilitiesGuard;
    private $contextProvider;
    private $logMessages;

    public function __construct(CapabilitiesGuard $accountCapabilitiesGuard, LogMessages $logMessages, ?callable $contextProvider = null)
    {
        $this->accountCapabilitiesGuard = $accountCapabilitiesGuard;
        $this->contextProvider = $contextProvider;
        $this->logMessages = $logMessages;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($command, callable $next)
    {
        if (!$command instanceof CapabilityCoveringCommand) {
            return $next($command);
        }

        $accountId = $command->account();
        $context = null !== $this->contextProvider ? ($this->contextProvider)($command, $accountId) : [];
        $messages = $this->accountCapabilitiesGuard->allowedTo($accountId, $context, $command::getCapability());
        $this->logMessages->merge($messages);

        if ($messages->hasErrors()) {
            return false;
        }

        return $next($command);
    }
}
