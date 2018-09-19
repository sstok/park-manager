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

namespace ParkManager\Module\WebhostingModule\Tests\Fixtures\Application\Mailbox;

use ParkManager\Module\WebhostingModule\Application\AccountIdAwareCommand;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountId;

final class CreateMailbox implements AccountIdAwareCommand
{
    private $accountId;
    private $size;

    public function __construct(string $accountId, $size)
    {
        $this->accountId = WebhostingAccountId::fromString($accountId);
        $this->size      = $size;
    }

    public function account(): WebhostingAccountId
    {
        return $this->accountId;
    }

    public function sizeInBytes()
    {
        return $this->size;
    }
}
