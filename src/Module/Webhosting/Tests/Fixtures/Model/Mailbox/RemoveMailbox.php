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

namespace ParkManager\Module\Webhosting\Tests\Fixtures\Model\Mailbox;

use ParkManager\Module\Webhosting\Model\Account\AccountIdAwareCommand;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccountId;
use Prooph\Common\Messaging\Command;
use Prooph\Common\Messaging\PayloadTrait;

final class RemoveMailbox extends Command implements AccountIdAwareCommand
{
    use PayloadTrait;

    private $accountId;

    public function __construct(WebhostingAccountId $accountId)
    {
        $this->accountId = $accountId;
        $this->init();
    }

    public function account(): WebhostingAccountId
    {
        return $this->accountId;
    }
}
