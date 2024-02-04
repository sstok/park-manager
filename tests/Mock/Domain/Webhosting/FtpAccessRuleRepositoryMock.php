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
use ParkManager\Domain\Webhosting\Ftp\AccessRule;
use ParkManager\Domain\Webhosting\Ftp\AccessRuleId;
use ParkManager\Domain\Webhosting\Ftp\AccessRuleRepository;
use ParkManager\Domain\Webhosting\Ftp\AccessRuleStrategy;
use ParkManager\Domain\Webhosting\Ftp\Exception\AccessRuleNotFound;
use ParkManager\Domain\Webhosting\Ftp\FtpUserId;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class FtpAccessRuleRepositoryMock implements AccessRuleRepository
{
    /** @use MockRepository<AccessRule> */
    use MockRepository;

    public function get(AccessRuleId $id): AccessRule
    {
        return $this->mockDoGetById($id);
    }

    protected function getFieldsIndexMapping(): array
    {
        return [
            'username' => '#username',
        ];
    }

    protected function getFieldsIndexMultiMapping(): array
    {
        return [
            'space' => static fn (AccessRule $rule) => $rule->space->id->toString(),
            'user' => static fn (AccessRule $rule) => $rule->user?->id->toString(),
        ];
    }

    public function hasAnyAllow(FtpUserId | SpaceId $id): bool
    {
        if ($id instanceof SpaceId) {
            $result = $this->mockDoGetMultiByField('space', $id->toString());
        } else {
            $result = $this->mockDoGetMultiByField('user', $id->toString());
        }

        /** @var AccessRule $rule */
        foreach ($result as $rule) {
            if ($rule->strategy === AccessRuleStrategy::ALLOW) {
                return true;
            }
        }

        return false;
    }

    public function allOfSpace(SpaceId $space): ResultSet
    {
        return $this->mockDoGetMultiByField('space', $space->toString());
    }

    public function allOfUser(FtpUserId $user): ResultSet
    {
        return $this->mockDoGetMultiByField('user', $user->toString());
    }

    public function save(AccessRule $rule): void
    {
        $this->mockDoSave($rule);
    }

    public function remove(AccessRule $rule): void
    {
        $this->mockDoRemove($rule);
    }

    protected function throwOnNotFound(mixed $key): void
    {
        throw new AccessRuleNotFound((string) $key);
    }
}
