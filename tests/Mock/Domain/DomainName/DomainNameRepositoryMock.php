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
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceNotFound;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Mock\Domain\MockRepository;

final class DomainNameRepositoryMock implements DomainNameRepository
{
    /** @use MockRepository<DomainName> */
    use MockRepository;

    protected function getFieldsIndexMapping(): array
    {
        return [
            'full_name' => static function (DomainName $domainName) {
                return $domainName->namePair->toString();
            },
            'name' => static function (DomainName $domainName) {
                return $domainName->namePair->name;
            },
            'tld' => static function (DomainName $domainName) {
                return $domainName->namePair->tld;
            },
            'space_primary_id' => static function (DomainName $model) {
                if ($model->isPrimary()) {
                    return (string) $model->getSpace()->getId();
                }

                return $model->getNamePair()->toString();
            },
        ];
    }

    protected function getFieldsIndexMultiMapping(): array
    {
        return [
            'owner' => static function (DomainName $domainName) {
                return $domainName->owner === null ? null : (string) $domainName->owner->id;
            },
            'space' => static function (DomainName $domainName) {
                return $domainName->space === null ? null : (string) $domainName->space->getId();
            },
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

    public function allFromOwner(?UserId $userId): iterable
    {
        if ($userId === null) {
            return $this->mockDoGetMultiByField('owner', null);
        }

        return $this->mockDoGetMultiByField('owner', (string) $userId);
    }

    public function allAccessibleBy(?UserId $userId): iterable
    {
        $found = [];

        /** @var DomainName $entity */
        foreach ($this->storedById as $id => $entity) {
            if (UserId::equalsValue($entity->owner, $userId, 'id')) {
                $found[$id] = $entity;
            } elseif ($entity->space !== null && UserId::equalsValue($entity->space->owner, $userId, 'id')) {
                $found[$id] = $entity;
            }
        }

        return $found;
    }

    public function allFromSpace(SpaceId $id): iterable
    {
        return $this->mockDoGetMultiByField('space', (string) $id);
    }

    public function save(DomainName $domainName): void
    {
        if ($domainName->isPrimary() && $domainName->getSpace() !== null) {
            try {
                $primaryDomainName = $this->getPrimaryOf($domainName->getSpace()->getId());
            } catch (WebhostingSpaceNotFound $e) {
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
        if ($domainName->isPrimary() && $domainName->getSpace() !== null) {
            throw new CannotRemovePrimaryDomainName(
                $domainName->getId(),
                $domainName->getSpace()->getId()
            );
        }

        $this->mockDoRemove($domainName);
    }

    protected function throwOnNotFound($key): void
    {
        if (\mb_strpos($key, '.') !== false) {
            throw DomainNameNotFound::withName(new DomainNamePair(...\explode('.', $key, 2)));
        }

        throw DomainNameNotFound::withId(DomainNameId::fromString($key));
    }
}
