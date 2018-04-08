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

use ParkManager\Component\ApplicationFoundation\Message\ServiceMessages;
use ParkManager\Module\Webhosting\Domain\Account\WebhostingAccountId;
use ParkManager\Module\Webhosting\Domain\Account\WebhostingAccountRepository;
use ParkManager\Module\Webhosting\Domain\Package\CapabilitiesGuard;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class AccountCapabilitiesGuard implements CapabilitiesGuard
{
    private $accountRepository;
    private $capabilitiesManager;

    public function __construct(WebhostingAccountRepository $accountRepository, CapabilitiesRegistry $capabilitiesManager)
    {
        $this->accountRepository = $accountRepository;
        $this->capabilitiesManager = $capabilitiesManager;
    }

    public function allowedTo(WebhostingAccountId $accountId, array $context, string ...$capabilityNames): ServiceMessages
    {
        $account = $this->accountRepository->get($accountId);
        $capabilities = $account->capabilities();
        $messages = new ServiceMessages();

        foreach ($capabilityNames as $capabilityName) {
            if (!$capabilities->has($capabilityName)) {
                continue;
            }

            if (null !== $this->capabilitiesManager->getConfig($capabilityName)['guard']) {
                $this->capabilitiesManager->getGuard($capabilityName)->isAllowed(
                    $capabilities->get($capabilityName),
                    $context,
                    $account,
                    $messages
                );
            }
        }

        return $messages;
    }
}
