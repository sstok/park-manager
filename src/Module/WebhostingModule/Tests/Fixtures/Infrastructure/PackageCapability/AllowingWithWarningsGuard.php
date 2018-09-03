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

namespace ParkManager\Module\WebhostingModule\Tests\Fixtures\Infrastructure\PackageCapability;

use ParkManager\Component\ApplicationFoundation\Message\ServiceMessage;
use ParkManager\Component\ApplicationFoundation\Message\ServiceMessages;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccount;
use ParkManager\Module\WebhostingModule\Domain\Package\Capability;
use ParkManager\Module\WebhostingModule\Infrastructure\Service\Package\CapabilityGuard;

final class AllowingWithWarningsGuard implements CapabilityGuard
{
    public function decide(Capability $configuration, array $context, WebhostingAccount $account, ServiceMessages $messages): bool
    {
        $messages->add(ServiceMessage::warning('Hold it there, you are about to get stuck '.($context['limit'] ?? 'NULL')));

        return true;
    }
}
