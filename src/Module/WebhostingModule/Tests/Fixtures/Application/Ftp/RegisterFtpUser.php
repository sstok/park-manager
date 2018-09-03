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

namespace ParkManager\Module\WebhostingModule\Tests\Fixtures\Application\Ftp;

use ParkManager\Module\WebhostingModule\Application\AccountIdAwareCommand;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountId;

final class RegisterFtpUser implements AccountIdAwareCommand
{
    private $accountId;

    public function __construct(string $accountId)
    {
        $this->accountId = WebhostingAccountId::fromString($accountId);
    }

    public function account(): WebhostingAccountId
    {
        return $this->accountId;
    }
}
