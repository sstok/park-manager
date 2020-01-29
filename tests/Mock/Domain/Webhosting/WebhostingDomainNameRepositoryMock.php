<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain\Webhosting;

use ParkManager\Domain\Webhosting\DomainName;
use ParkManager\Domain\Webhosting\DomainName\Exception\CannotRemovePrimaryDomainName;
use ParkManager\Domain\Webhosting\DomainName\Exception\WebhostingDomainNameNotFound;
use ParkManager\Domain\Webhosting\DomainName\WebhostingDomainName;
use ParkManager\Domain\Webhosting\DomainName\WebhostingDomainNameId;
use ParkManager\Domain\Webhosting\DomainName\WebhostingDomainNameRepository;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceNotFound;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceId;
use ParkManager\Tests\Mock\Domain\MockRepository;

final class WebhostingDomainNameRepositoryMock implements WebhostingDomainNameRepository
{
    use MockRepository;

    protected function getFieldsIndexMapping(): array
    {
        return [
            'name' => function (WebhostingDomainName $model) {
                return $model->getDomainName()->name;
            },
            'full_name' => function (WebhostingDomainName $model) {
                return $model->getDomainName()->toString();
            },
            'space_primary_id' => function (WebhostingDomainName $model) {
                if ($model->isPrimary()) {
                    return (string) $model->getSpace()->getId();
                }

                return $model->getDomainName()->toString();
            },
        ];
    }

    public function get(WebhostingDomainNameId $id): WebhostingDomainName
    {
        return $this->mockDoGetById($id);
    }

    public function getPrimaryOf(WebhostingSpaceId $id): WebhostingDomainName
    {
        if (! $this->mockDoHasByField('space_primary_id', $id->toString())) {
            throw WebhostingSpaceNotFound::withId($id);
        }

        return $this->mockDoGetByField('space_primary_id', $id->toString());
    }

    public function findByFullName(DomainName $name): ?WebhostingDomainName
    {
        if (! $this->mockDoHasByField('full_name', $name->toString())) {
            return null;
        }

        return $this->mockDoGetByField('full_name', $name->toString());
    }

    public function save(WebhostingDomainName $domainName): void
    {
        if ($domainName->isPrimary()) {
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

    public function remove(WebhostingDomainName $domainName): void
    {
        if ($domainName->isPrimary()) {
            throw CannotRemovePrimaryDomainName::of(
                $domainName->getId(),
                $domainName->getSpace()->getId()
            );
        }

        $this->mockDoRemove($domainName);
    }

    protected function throwOnNotFound($key): void
    {
        throw new WebhostingDomainNameNotFound($key);
    }
}
