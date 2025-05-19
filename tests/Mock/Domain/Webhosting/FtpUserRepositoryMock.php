<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain\Webhosting;

use Lifthill\Component\Common\Domain\ResultSet;
use Lifthill\Component\Common\Test\MockRepository;
use ParkManager\Domain\Webhosting\Ftp\Exception\FtpUsernameAlreadyExists;
use ParkManager\Domain\Webhosting\Ftp\Exception\FtpUserNotFound;
use ParkManager\Domain\Webhosting\Ftp\FtpUser;
use ParkManager\Domain\Webhosting\Ftp\FtpUserId;
use ParkManager\Domain\Webhosting\Ftp\FtpUserRepository;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class FtpUserRepositoryMock implements FtpUserRepository
{
    /** @use MockRepository<FtpUser> */
    use MockRepository;

    protected function getFieldsIndexMapping(): array
    {
        return [
            'username' => static fn (FtpUser $user) => \sprintf('%s.%s', $user->username, $user->domainName->namePair),
        ];
    }

    protected function getFieldsIndexMultiMapping(): array
    {
        return [
            'space' => static fn (FtpUser $user) => $user->space->id->toString(),
        ];
    }

    public function get(FtpUserId $id): FtpUser
    {
        return $this->mockDoGetById($id);
    }

    public function all(SpaceId $space): ResultSet
    {
        return $this->mockDoGetMultiByField('space', $space->toString());
    }

    public function save(FtpUser $user): void
    {
        try {
            $existing = $this->mockDoGetByField('username', \sprintf('%s.%s', $user->username, $user->domainName->namePair));

            if (! $existing->id->equals($user->id)) {
                throw new FtpUsernameAlreadyExists($user->username, $existing->domainName->namePair, $existing->id);
            }
        } catch (FtpUserNotFound) {
            // No-op
        }

        $this->mockDoSave($user);
    }

    public function remove(FtpUser $user): void
    {
        $this->mockDoRemove($user);
    }

    protected function throwOnNotFound(mixed $key): void
    {
        throw new FtpUserNotFound((string) $key);
    }
}
