<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain\Webhosting;

use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\Webhosting\Email\Exception\EmailForwardNotFound;
use ParkManager\Domain\Webhosting\Email\Forward;
use ParkManager\Domain\Webhosting\Email\ForwardId;
use ParkManager\Domain\Webhosting\Email\ForwardRepository;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Mock\Domain\MockRepository;

/** @internal */
final class ForwardRepositoryMock implements ForwardRepository
{
    use MockRepository;

    public const ID1 = 'c0a358cb-cecb-4faa-b274-9b4f7e8294cc';

    protected function getFieldsIndexMapping(): array
    {
        return [
            'full_address' => static fn (Forward $forward): string => \sprintf('%s@%s', $forward->address, $forward->domainName->getNamePair()->toString()),
        ];
    }

    protected function getFieldsIndexMultiMapping(): array
    {
        return [
            'space_id' => static fn (Forward $forward): string => $forward->space->id->toString(),
        ];
    }

    public function get(ForwardId $id): Forward
    {
        return $this->mockDoGetById($id);
    }

    public function getByName(string $address, DomainNamePair $domainNamePair): Forward
    {
        return $this->mockDoGetByField('full_address', $address . '@' . $domainNamePair->toString());
    }

    public function allBySpace(SpaceId $space): iterable
    {
        return $this->mockDoGetMultiByField('space_id', $space->toString());
    }

    public function countBySpace(SpaceId $space): int
    {
        return \count([...$this->mockDoGetMultiByField('space_id', $space->toString())]);
    }

    public function save(Forward $forward): void
    {
        $this->mockDoSave($forward);
    }

    public function remove(Forward $forward): void
    {
        $this->mockDoRemove($forward);
    }

    protected function throwOnNotFound($key): void
    {
        throw new EmailForwardNotFound($key);
    }
}
