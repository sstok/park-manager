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
use ParkManager\Domain\Webhosting\Email\Exception\AddressAlreadyExists;
use ParkManager\Domain\Webhosting\Email\Exception\EmailForwardNotFound;
use ParkManager\Domain\Webhosting\Email\Forward;
use ParkManager\Domain\Webhosting\Email\ForwardId;
use ParkManager\Domain\Webhosting\Email\ForwardRepository;
use ParkManager\Domain\Webhosting\Space\SpaceId;

/** @internal */
final class ForwardRepositoryMock implements ForwardRepository
{
    /** @use MockRepository<Forward> */
    use MockRepository;

    public const ID1 = 'c0a358cb-cecb-4faa-b274-9b4f7e8294cc';

    /**
     * @return array<string, string|\Closure>
     */
    protected function getFieldsIndexMapping(): array
    {
        return [
            'full_address' => static fn (Forward $forward): string => \sprintf('%s@%s', $forward->address, $forward->domainName->namePair->toString()),
        ];
    }

    /**
     * @return array<string, string|\Closure>
     */
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

    public function hasName(string $address, DomainNamePair $domainNamePair): bool
    {
        try {
            $this->getByName($address, $domainNamePair);

            return true;
        } catch (EmailForwardNotFound) {
            return false;
        }
    }

    public function allBySpace(SpaceId $space): ResultSet
    {
        return $this->mockDoGetMultiByField('space_id', $space->toString());
    }

    public function countBySpace(SpaceId $space): int
    {
        return \count([...$this->mockDoGetMultiByField('space_id', $space->toString())]);
    }

    public function save(Forward $forward): void
    {
        if ($forward->addressChanged) {
            try {
                if ($this->getByName($forward->address, $forward->domainName->namePair) !== $forward) {
                    throw new AddressAlreadyExists($forward->address, $forward->domainName->namePair);
                }
            } catch (EmailForwardNotFound) {
                // No-op.
            }
        }

        $this->mockDoSave($forward);
    }

    public function remove(Forward $forward): void
    {
        $this->mockDoRemove($forward);
    }

    protected function throwOnNotFound(mixed $key): void
    {
        throw new EmailForwardNotFound($key);
    }
}
