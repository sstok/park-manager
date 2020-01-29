<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain\Webhosting;

use ParkManager\Domain\Webhosting\Space\Exception\CannotRemoveActiveWebhostingSpace;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceNotFound;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceId;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceRepository;
use ParkManager\Tests\Mock\Domain\MockRepository;

/** @internal */
final class SpaceRepositoryMock implements WebhostingSpaceRepository
{
    use MockRepository;

    /**
     * @inheritDoc
     */
    public function get(WebhostingSpaceId $id): Space
    {
        return $this->mockDoGetById($id);
    }

    public function save(Space $space): void
    {
        $this->mockDoSave($space);
    }

    public function remove(Space $space): void
    {
        if (!$space->isMarkedForRemoval()) {
            throw new CannotRemoveActiveWebhostingSpace($space->getId());
        }

        $this->mockDoRemove($space);
    }

    protected function throwOnNotFound($key): void
    {
        throw new WebhostingSpaceNotFound($key);
    }
}
