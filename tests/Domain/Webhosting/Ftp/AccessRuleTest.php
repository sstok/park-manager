<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\Ftp;

use IPLib\Factory as IPFactory;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Owner;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Ftp\AccessRule;
use ParkManager\Domain\Webhosting\Ftp\AccessRuleId;
use ParkManager\Domain\Webhosting\Ftp\AccessRuleStrategy;
use ParkManager\Domain\Webhosting\Ftp\FtpUser;
use ParkManager\Domain\Webhosting\Ftp\FtpUserId;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class AccessRuleTest extends TestCase
{
    /** @test */
    public function it_creates_for_space(): void
    {
        $space = $this->getSpace();

        $rule = AccessRule::createForSpace(
            $id = AccessRuleId::fromString('017cff0a-04e4-cbb5-b46e-dab5d1d0b433'),
            $space,
            $ip = IPFactory::parseAddressString('200.100.100.10')
        );

        self::assertSame($id, $rule->id);
        self::assertSame($space, $rule->space);
        self::assertNull($rule->user);
        self::assertSame($ip, $rule->address);
        self::assertSame(AccessRuleStrategy::get('DENY'), $rule->strategy);

        $rule = AccessRule::createForSpace(
            $id = AccessRuleId::fromString('017cff0a-04e4-cbb5-b46e-dab5d1d0b433'),
            $space,
            $ip = IPFactory::parseAddressString('200.100.100.10'),
            AccessRuleStrategy::get('ALLOW')
        );

        self::assertSame($id, $rule->id);
        self::assertSame($space, $rule->space);
        self::assertNull($rule->user);
        self::assertSame($ip, $rule->address);
        self::assertSame(AccessRuleStrategy::get('ALLOW'), $rule->strategy);
    }

    /** @test */
    public function it_creates_for_user(): void
    {
        $space = $this->getSpace();
        $domainName = DomainName::registerForSpace(DomainNameId::fromString('017d0538-df9a-a7ec-5e28-85c76d327fb9'), $space, new DomainNamePair('example', 'com'));

        $user = new FtpUser(FtpUserId::fromString('017cff6c-2677-e8ed-4d2e-43b964c764b3'), $space, 'user1', 'nope', $domainName);

        $rule = AccessRule::createForUser(
            $id = AccessRuleId::fromString('017cff0a-04e4-cbb5-b46e-dab5d1d0b433'),
            $user,
            $ip = IPFactory::parseAddressString('200.100.100.10')
        );

        self::assertSame($id, $rule->id);
        self::assertSame($space, $rule->space);
        self::assertSame($user, $rule->user);
        self::assertSame($ip, $rule->address);
        self::assertSame(AccessRuleStrategy::get('DENY'), $rule->strategy);

        $rule = AccessRule::createForUser(
            $id = AccessRuleId::fromString('017cff0a-04e4-cbb5-b46e-dab5d1d0b433'),
            $user,
            $ip = IPFactory::parseAddressString('200.100.100.10'),
            AccessRuleStrategy::get('ALLOW')
        );

        self::assertSame($id, $rule->id);
        self::assertSame($space, $rule->space);
        self::assertSame($user, $rule->user);
        self::assertSame($ip, $rule->address);
        self::assertSame(AccessRuleStrategy::get('ALLOW'), $rule->strategy);
    }

    private function getSpace(): Space
    {
        $owner = Owner::byUser(
            User::register(
                UserId::fromString('57824f85-d5db-4732-8333-cf51a0b268c2'),
                new EmailAddress('John2me@mustash.com'),
                'John the II',
                'ashTong@8r949029'
            )
        );

        return Space::registerWithCustomConstraints(
            SpaceId::fromString('65f41c60-89b6-4e7d-870c-1dd6d61157aa'),
            $owner,
            new Constraints()
        );
    }
}
