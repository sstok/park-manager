<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain\Webhosting;

use ParkManager\Domain\Owner;
use ParkManager\Domain\OwnerId;
use ParkManager\Domain\ResultSet;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Domain\Webhosting\Space\Exception\CannotRemoveActiveWebhostingSpace;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceNotFound;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceRepository;
use ParkManager\Tests\Mock\Domain\MockRepository;
use ParkManager\Tests\Mock\Domain\OwnerRepositoryMock;

/** @internal */
final class SpaceRepositoryMock implements WebhostingSpaceRepository
{
    use MockRepository;

    public const ID1 = 'a52f33ab-a419-4b62-8ec5-5dad33e8af69';

    private static Owner $adminOwner;

    public function get(SpaceId $id): Space
    {
        return $this->mockDoGetById($id);
    }

    public function allWithAssignedPlan(PlanId $id): ResultSet
    {
        return $this->mockDoGetMultiByField('plan', $id->toString());
    }

    public function allFromOwner(OwnerId $id): ResultSet
    {
        return $this->mockDoGetMultiByField('owner', $id->toString());
    }

    public function save(Space $space): void
    {
        $this->mockDoSave($space);
    }

    public function remove(Space $space): void
    {
        if (! $space->isMarkedForRemoval()) {
            throw new CannotRemoveActiveWebhostingSpace($space->id);
        }

        $this->mockDoRemove($space);
    }

    public static function createSpace(string $id = self::ID1, ?Owner $owner = null, ?Constraints $constraints = null): Space
    {
        self::$adminOwner ??= (new OwnerRepositoryMock())->getAdminOrganization();

        return Space::registerWithCustomConstraints(SpaceId::fromString($id), $owner ?? self::$adminOwner, $constraints ?? new Constraints());
    }

    protected function throwOnNotFound($key): void
    {
        throw new WebhostingSpaceNotFound($key);
    }

    protected function getFieldsIndexMultiMapping(): array
    {
        return [
            'plan' => static fn (Space $space): ?string => $space->plan !== null ? $space->plan->id->toString() : null,
            'owner' => static fn (Space $space): string => (string) $space->owner,
        ];
    }
}
