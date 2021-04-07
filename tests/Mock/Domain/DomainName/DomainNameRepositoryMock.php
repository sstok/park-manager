<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain\DomainName;

use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\DomainName\Exception\CannotRemovePrimaryDomainName;
use ParkManager\Domain\DomainName\Exception\DomainNameNotFound;
use ParkManager\Domain\OwnerId;
use ParkManager\Domain\ResultSet;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceNotFound;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Mock\Domain\MockRepoResultSet;
use ParkManager\Tests\Mock\Domain\MockRepository;

final class DomainNameRepositoryMock implements DomainNameRepository
{
    /** @use MockRepository<DomainName> */
    use MockRepository;

    protected function getFieldsIndexMapping(): array
    {
        return [
            'full_name' => static fn (DomainName $domainName) => $domainName->namePair->toString(),
            'name' => static fn (DomainName $domainName) => $domainName->namePair->name,
            'tld' => static fn (DomainName $domainName) => $domainName->namePair->tld,
            'space_primary_id' => static function (DomainName $model) {
                if ($model->space !== null && $model->isPrimary()) {
                    return (string) $model->space->id;
                }

                return 'null';
            },
        ];
    }

    protected function getFieldsIndexMultiMapping(): array
    {
        return [
            'owner' => static fn (DomainName $domainName) => $domainName->owner === null ? null : (string) $domainName->owner->id,
            'space' => static fn (DomainName $domainName) => $domainName->space === null ? null : (string) $domainName->space->id,
        ];
    }

    public function get(DomainNameId $id): DomainName
    {
        return $this->mockDoGetById($id);
    }

    public function getPrimaryOf(SpaceId $id): DomainName
    {
        if (! $this->mockDoHasByField('space_primary_id', $id->toString())) {
            throw WebhostingSpaceNotFound::withId($id);
        }

        return $this->mockDoGetByField('space_primary_id', $id->toString());
    }

    public function getByName(DomainNamePair $name): DomainName
    {
        return $this->mockDoGetByField('full_name', $name->toString());
    }

    public function allFromOwner(OwnerId $id): ResultSet
    {
        return $this->mockDoGetMultiByField('owner', (string) $id);
    }

    public function allAccessibleBy(OwnerId $ownerId): ResultSet
    {
        $found = [];

        /** @var DomainName $entity */
        foreach ($this->storedById as $id => $entity) {
            if (OwnerId::equalsValueOfEntity($ownerId, $entity->owner, 'id')) {
                $found[$id] = $entity;
            } elseif ($entity->space !== null && OwnerId::equalsValueOfEntity($ownerId, $entity->space->owner, 'id')) {
                $found[$id] = $entity;
            }
        }

        return new MockRepoResultSet($found);
    }

    public function allFromSpace(SpaceId $id): ResultSet
    {
        return $this->mockDoGetMultiByField('space', (string) $id);
    }

    public function all(): ResultSet
    {
        return $this->mockDoGetAll();
    }

    public function save(DomainName $domainName): void
    {
        if ($domainName->isPrimary() && $domainName->space !== null) {
            try {
                $primaryDomainName = $this->getPrimaryOf($domainName->space->id);
            } catch (WebhostingSpaceNotFound) {
                $primaryDomainName = $domainName;
            }

            // If there is a primary marking for another DomainName (within in this space)
            // remove the primary marking for that DomainName.
            if ($primaryDomainName !== $domainName) {
                // There is no setter function for the Model as this is an implementation detail.
                (function (): void {
                    $this->primary = false;
                })->call($primaryDomainName);

                $this->mockDoSave($primaryDomainName);
            }
        }

        $this->mockDoSave($domainName);
    }

    public function remove(DomainName $domainName): void
    {
        if ($domainName->isPrimary() && $domainName->space !== null) {
            throw new CannotRemovePrimaryDomainName(
                $domainName->id,
                $domainName->space->id
            );
        }

        $this->mockDoRemove($domainName);
    }

    protected function throwOnNotFound($key): void
    {
        if ($key instanceof DomainNameId) {
            throw DomainNameNotFound::withId($key);
        }

        if (\mb_strpos($key, '.') !== false) {
            throw DomainNameNotFound::withName(new DomainNamePair(...\explode('.', $key, 2)));
        }

        throw DomainNameNotFound::withId(DomainNameId::fromString($key));
    }
}
