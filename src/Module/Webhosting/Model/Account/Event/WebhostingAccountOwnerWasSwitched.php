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

namespace ParkManager\Module\Webhosting\Model\Account\Event;

use ParkManager\Component\Model\DomainEvent;
use ParkManager\Module\Webhosting\Model\Account\HasWebhostingAccountId;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccountId;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccountOwner;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class WebhostingAccountOwnerWasSwitched extends DomainEvent
{
    use HasWebhostingAccountId;

    /**
     * @var WebhostingAccountOwner|null
     */
    private $oldOwner;

    /**
     * @var WebhostingAccountOwner|null
     */
    private $newOwner;

    public static function withData(WebhostingAccountId $id, WebhostingAccountOwner $oldOwner, WebhostingAccountOwner $newOwner): self
    {
        /** @var self $event */
        $event = self::occur($id->toString(), [
            'old_owner' => $oldOwner->toString(),
            'new_owner' => $newOwner->toString(),
        ]);
        $event->newOwner = $newOwner;
        $event->oldOwner = $oldOwner;
        $event->id = $id;

        return $event;
    }

    public function oldOwner(): WebhostingAccountOwner
    {
        if (null === $this->oldOwner) {
            $this->oldOwner = WebhostingAccountOwner::fromString($this->payload['old_owner']);
        }

        return $this->oldOwner;
    }

    public function newOwner(): WebhostingAccountOwner
    {
        if (null === $this->newOwner) {
            $this->newOwner = WebhostingAccountOwner::fromString($this->payload['new_owner']);
        }

        return $this->newOwner;
    }
}
