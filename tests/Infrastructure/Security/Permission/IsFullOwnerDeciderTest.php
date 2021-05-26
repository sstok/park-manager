<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Security\Permission;

use ParkManager\Domain\Organization\Organization;
use ParkManager\Domain\Organization\OrganizationId;
use ParkManager\Domain\Owner;
use ParkManager\Domain\OwnerId;
use ParkManager\Domain\User\UserId;
use ParkManager\Infrastructure\Security\Permission\IsFullOwner;
use ParkManager\Infrastructure\Security\Permission\IsFullOwnerDecider;
use ParkManager\Infrastructure\Security\PermissionAccessManager;
use ParkManager\Infrastructure\Security\PermissionDecider;
use ParkManager\Infrastructure\Security\SecurityUser;
use ParkManager\Tests\Mock\Domain\Organization\OrganizationRepositoryMock;
use ParkManager\Tests\Mock\Domain\OwnerRepositoryMock;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * @internal
 */
final class IsFullOwnerDeciderTest extends TestCase
{
    use ProphecyTrait;

    public const USER_ID = 'e29e2caf-5fc8-4314-9ecd-fd29708b412b';
    public const USER_ID2 = '8e37a3b4-c61e-42c2-a184-c3696b1e21fa';

    private UserRepositoryMock $userRepository;
    private OrganizationRepositoryMock $orgRepository;
    private OwnerRepositoryMock $ownerRepository;
    private IsFullOwnerDecider $decider;

    protected function setUp(): void
    {
        $user = UserRepositoryMock::createUser(id: self::USER_ID);
        $this->userRepository = new UserRepositoryMock([$user]);
        $this->orgRepository = new OrganizationRepositoryMock($this->userRepository);
        $this->ownerRepository = new OwnerRepositoryMock([Owner::byUser($user)], $this->orgRepository);

        $this->decider = new IsFullOwnerDecider($this->orgRepository, $this->userRepository);
    }

    /** @test */
    public function it_decides_deny_if_administrator_org_owned_and_user_is_not_admin(): void
    {
        $securityUser = new SecurityUser(self::USER_ID, 'Nope', true, ['ROLE_USER']);
        $token = $this->createAuthenticationToken($securityUser);

        $permission = new IsFullOwner($this->ownerRepository->getAdminOrganization());
        self::assertSame(PermissionDecider::DECIDE_DENY, $this->decider->decide($permission, $token, $securityUser, $this->createMock(PermissionAccessManager::class)));
    }

    private function createAuthenticationToken(SecurityUser $securityUser): AbstractToken
    {
        $token = $this->getMockForAbstractClass(AbstractToken::class, [$securityUser->getRoles()]);
        $token->setUser($securityUser);

        return $token;
    }

    /** @test */
    public function it_decides_allow_if_administrator_org_owned_and_user_is_admin(): void
    {
        $securityUser = new SecurityUser(self::USER_ID, 'Nope', true, ['ROLE_USER', 'ROLE_ADMIN']);
        $token = $this->createAuthenticationToken($securityUser);

        $permission = new IsFullOwner($this->ownerRepository->getAdminOrganization());
        self::assertSame(PermissionDecider::DECIDE_ALLOW, $this->decider->decide($permission, $token, $securityUser, $this->createMock(PermissionAccessManager::class)));
    }

    /** @test */
    public function it_decides_allow_if_owner_equals_user_id(): void
    {
        $securityUser = new SecurityUser(self::USER_ID, 'Nope', true, ['ROLE_USER']);
        $token = $this->createAuthenticationToken($securityUser);

        $permission = new IsFullOwner($this->getOwner());
        self::assertSame(PermissionDecider::DECIDE_ALLOW, $this->decider->decide($permission, $token, $securityUser, $this->createMock(PermissionAccessManager::class)));
    }

    private function getOwner(string $id = self::USER_ID): Owner
    {
        return $this->ownerRepository->get(OwnerId::fromString($id));
    }

    /** @test */
    public function it_decides_abstain_if_user_not_maintainer_of_the_org_owner(): void
    {
        $securityUser = new SecurityUser(self::USER_ID, 'Nope', true, ['ROLE_USER']);
        $token = $this->createAuthenticationToken($securityUser);

        $this->orgRepository->save($org = new Organization(OrganizationId::fromString('d5d89567-9289-4500-ae5a-bfc26860e48d'), 'Globex Testing Inc. LLC'));
        $this->ownerRepository->save($owner = Owner::byOrganization($org));

        $permission = new IsFullOwner($owner);
        self::assertSame(PermissionDecider::DECIDE_ABSTAIN, $this->decider->decide($permission, $token, $securityUser, $this->createMock(PermissionAccessManager::class)));
    }

    /** @test */
    public function it_decides_allow_if_user_is_maintainer_of_the_org_owner(): void
    {
        $securityUser = new SecurityUser(self::USER_ID, 'Nope', true, ['ROLE_USER']);
        $token = $this->createAuthenticationToken($securityUser);

        $org = new Organization(OrganizationId::fromString('d5d89567-9289-4500-ae5a-bfc26860e48d'), 'Globex Testing Inc. LLC');
        $org->addMember($this->userRepository->get(UserId::fromString(self::USER_ID)));
        $this->orgRepository->save($org);

        $this->ownerRepository->save($owner = Owner::byOrganization($org));

        $permission = new IsFullOwner($owner);
        self::assertSame(PermissionDecider::DECIDE_ALLOW, $this->decider->decide($permission, $token, $securityUser, $this->createMock(PermissionAccessManager::class)));
    }
}
