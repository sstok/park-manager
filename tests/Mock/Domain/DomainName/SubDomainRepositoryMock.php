<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain\DomainName;

use Lifthill\Component\Common\Domain\ResultSet;
use Lifthill\Component\Common\Test\MockRepository;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Domain\Webhosting\SubDomain\Exception\SubDomainAlreadyExists;
use ParkManager\Domain\Webhosting\SubDomain\Exception\SubDomainNotFound;
use ParkManager\Domain\Webhosting\SubDomain\SubDomain;
use ParkManager\Domain\Webhosting\SubDomain\SubDomainNameId;
use ParkManager\Domain\Webhosting\SubDomain\SubDomainRepository;

final class SubDomainRepositoryMock implements SubDomainRepository
{
    /** @use MockRepository<Subdomain> */
    use MockRepository;

    /**
     * @return array<string, string|\Closure>
     */
    protected function getFieldsIndexMapping(): array
    {
        return [
            'full_name' => static fn (SubDomain $subDomain): string => sprintf('%s.%s', $subDomain->host->id->toString(), $subDomain->name),
        ];
    }

    /**
     * @return array<string, string|\Closure>
     */
    protected function getFieldsIndexMultiMapping(): array
    {
        return [
            'space' => static fn (SubDomain $subDomain): string => $subDomain->space->id->toString(),
        ];
    }

    public function get(SubDomainNameId $id): SubDomain
    {
        return $this->mockDoGetById($id);
    }

    public function allFromSpace(SpaceId $space): ResultSet
    {
        return $this->mockDoGetMultiByField('space', $space->toString());
    }

    public function save(SubDomain $subDomain): void
    {
        try {
            /** @var SubDomain $entity */
            $entity = $this->mockDoGetByField('full_name', sprintf('%s.%s', $subDomain->host->id->toString(), $subDomain->name));

            if (! $entity->id->equals($subDomain->id)) {
                throw new SubDomainAlreadyExists($subDomain->host->namePair, $subDomain->name, $entity->id->toString());
            }
        } catch (SubDomainNotFound) {
            // OK. Doesn't exist yet
        }

        $this->mockDoSave($subDomain);
    }

    public function remove(SubDomain $subDomain): void
    {
        $this->mockDoRemove($subDomain);
    }

    protected function throwOnNotFound(mixed $key): void
    {
        throw new SubDomainNotFound($key);
    }
}
