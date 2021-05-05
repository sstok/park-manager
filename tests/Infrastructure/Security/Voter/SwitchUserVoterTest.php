<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Security\Voter;

use ParkManager\Infrastructure\Security\SecurityUser;
use ParkManager\Infrastructure\Security\Voter\SwitchUserVoter;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\User;

/**
 * @internal
 */
final class SwitchUserVoterTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function it_grants_access_if_enabled_and_user_admin(): void
    {
        $token = $this->createToken(new SecurityUser('e29e2caf-5fc8-4314-9ecd-fd29708b412b', 'Nope', true, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']));
        $voter = new SwitchUserVoter();

        $toSwitchUser = new SecurityUser('e29e2caf-5fc8-4314-9ecd-fd29708b412b', 'Nope', true, ['ROLE_USER']);

        self::assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $toSwitchUser, [SwitchUserVoter::CAN_SWITCH_USER]));
        self::assertEquals(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $toSwitchUser, [true, SwitchUserVoter::CAN_SWITCH_USER]));
    }

    private function createToken(?object $user = null): TokenInterface
    {
        $tokenProphecy = $this->prophesize(TokenInterface::class);
        $tokenProphecy->getUser()->willReturn($user);

        return $tokenProphecy->reveal();
    }

    /**
     * @test
     * @dataProvider provideAbstainedAccessFor
     */
    public function it_abstains_access_when(object $currentUser, mixed $subject): void
    {
        $token = $this->createToken($currentUser);
        $voter = new SwitchUserVoter();

        self::assertEquals(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, $subject, [SwitchUserVoter::CAN_SWITCH_USER]));
    }

    public function provideAbstainedAccessFor(): iterable
    {
        $toSwitchUser = new SecurityUser('e29e2caf-5fc8-4314-9ecd-fd29708b412b', 'Nope', true, ['ROLE_USER']);

        yield 'not of the correct user' => [new User('hello', 'nope'), $toSwitchUser];
        yield 'not of the correct subject type (null)' => [new SecurityUser('e29e2caf-5fc8-4314-9ecd-fd29708b412b', 'Nope', true, ['ROLE_ADMIN']), null];
        yield 'not of the correct subject type (user)' => [new SecurityUser('e29e2caf-5fc8-4314-9ecd-fd29708b412b', 'Nope', true, ['ROLE_ADMIN']), new User('hello', 'nope')];
    }

    /**
     * @test
     * @dataProvider provideDeniedAccessFor
     */
    public function it_denies_access_when(object $currentUser, mixed $subject): void
    {
        $token = $this->createToken($currentUser);
        $voter = new SwitchUserVoter();

        self::assertEquals(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, [SwitchUserVoter::CAN_SWITCH_USER]));
    }

    public function provideDeniedAccessFor(): iterable
    {
        $toSwitchUser = new SecurityUser('e29e2caf-5fc8-4314-9ecd-fd29708b412b', 'Nope', true, ['ROLE_USER']);

        yield 'not an admin' => [new SecurityUser('e29e2caf-5fc8-4314-9ecd-fd29708b412b', 'Nope', true, ['ROLE_USER']), $toSwitchUser];
        yield 'not enabled' => [new SecurityUser('e29e2caf-5fc8-4314-9ecd-fd29708b412b', 'Nope', true, ['ROLE_ADMIN']), new SecurityUser('e29e2caf-5fc8-4314-9ecd-fd29708b412b', 'Nope', false, ['ROLE_USER'])];
        yield 'switching to admin' => [$user = new SecurityUser('e29e2caf-5fc8-4314-9ecd-fd29708b412b', 'Nope', true, ['ROLE_ADMIN']), $user];
    }

    /**
     * @test
     * @dataProvider provideIgnoredAttributes
     */
    public function it_abstains_access_when_different_attribute_is_used(string $attribute): void
    {
        $token = $this->createToken(new SecurityUser('e29e2caf-5fc8-4314-9ecd-fd29708b412b', 'Nope', true, ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']));
        $voter = new SwitchUserVoter();

        $toSwitchUser = new SecurityUser('e29e2caf-5fc8-4314-9ecd-fd29708b412b', 'Nope', true, ['ROLE_USER']);

        self::assertEquals(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, $toSwitchUser, [$attribute]));
    }

    public function provideIgnoredAttributes(): iterable
    {
        yield [AuthenticatedVoter::IS_AUTHENTICATED_FULLY];
        yield [AuthenticatedVoter::IS_AUTHENTICATED_REMEMBERED];
        yield [AuthenticatedVoter::IS_AUTHENTICATED_ANONYMOUSLY];
        yield [AuthenticatedVoter::IS_ANONYMOUS];
        yield [AuthenticatedVoter::IS_IMPERSONATOR];
        yield [AuthenticatedVoter::IS_REMEMBERED];
    }
}
