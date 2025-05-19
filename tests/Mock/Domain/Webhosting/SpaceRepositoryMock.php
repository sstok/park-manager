<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain\Webhosting;

use Lifthill\Component\Common\Domain\Model\DomainNamePair;
use Lifthill\Component\Common\Domain\ResultSet;
use Lifthill\Component\Common\Test\MockRepository;
use ParkManager\Domain\Owner;
use ParkManager\Domain\OwnerId;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\Domain\Webhosting\Space\Exception\CannotRemoveActiveWebhostingSpace;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceNotFound;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;
use ParkManager\Tests\Mock\Domain\OwnerRepositoryMock;

/** @internal */
final class SpaceRepositoryMock implements SpaceRepository
{
    /** @use MockRepository<Space> */
    use MockRepository;

    public const ID1 = 'a52f33ab-a419-4b62-8ec5-5dad33e8af69';

    private static Owner $adminOwner;

    public function get(SpaceId $id): Space
    {
        return $this->mockDoGetById($id);
    }

    public function all(): ResultSet
    {
        return $this->mockDoGetAll();
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
            throw CannotRemoveActiveWebhostingSpace::withId($space->id);
        }

        $this->mockDoRemove($space);
    }

    public static function createSpace(string $id = self::ID1, ?Owner $owner = null, ?Constraints $constraints = null, ?DomainNamePair $domainName = null): Space
    {
        self::$adminOwner ??= (new OwnerRepositoryMock())->getAdminOrganization();

        $space = Space::registerWithCustomConstraints(
            SpaceId::fromString($id),
            $owner ?? self::$adminOwner,
            $constraints ?? new Constraints()
        );
        $space->setPrimaryDomainLabel($domainName ?? new DomainNamePair('example', 'com'));

        return $space;
    }

    protected function throwOnNotFound(mixed $key): void
    {
        throw WebhostingSpaceNotFound::withId($key);
    }

    /**
     * @return array<string, string|\Closure>
     */
    protected function getFieldsIndexMultiMapping(): array
    {
        return [
            'plan' => static fn (Space $space): ?string => $space->plan !== null ? $space->plan->id->toString() : null,
            'owner' => static fn (Space $space): string => (string) $space->owner,
        ];
    }
}
