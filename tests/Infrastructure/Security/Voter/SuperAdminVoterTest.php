<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Security\Voter;

use ParkManager\Infrastructure\Security\SecurityUser;
use ParkManager\Infrastructure\Security\Voter\SuperAdminVoter;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\InMemoryUser as User;

/**
 * @internal
 */
final class SuperAdminVoterTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_grants_access_if_enabled_and_user_admin(): void
    {
        $token = $this->createToken(new SecurityUser('e29e2caf-5fc8-4314-9ecd-fd29708b412b', 'Nope', true, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']));
        $voter = new SuperAdminVoter();

        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, null, []));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, new \stdClass(), []));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, new \stdClass(), ['ACTION_NEW']));
    }

    private function createToken(?object $user = null): TokenInterface
    {
        $tokenProphecy = $this->prophesize(TokenInterface::class);
        $tokenProphecy->getUser()->willReturn($user);

        return $tokenProphecy->reveal();
    }

    /**
     * @test
     *
     * @dataProvider provideIt_abstains_access_whenCases
     */
    public function it_abstains_access_when(object $user): void
    {
        $token = $this->createToken($user);
        $voter = new SuperAdminVoter();

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, null, []));
    }

    /**
     * @return \Generator<string, array{0: SecurityUser|User}>
     */
    public static function provideIt_abstains_access_whenCases(): iterable
    {
        yield 'not a super admin' => [new SecurityUser('e29e2caf-5fc8-4314-9ecd-fd29708b412b', 'Nope', true, ['ROLE_ADMIN'])];
        yield 'not an admin' => [new SecurityUser('e29e2caf-5fc8-4314-9ecd-fd29708b412b', 'Nope', true, ['ROLE_USER'])];
        yield 'access not enabled' => [new SecurityUser('e29e2caf-5fc8-4314-9ecd-fd29708b412b', 'Nope', false, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'])];
        yield 'not of the right type' => [new User('hello', 'nope')];
    }
}
