<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Administrator;

use Assert\AssertionFailedException;
use DateTimeImmutable;
use ParkManager\Domain\Administrator\Administrator;
use ParkManager\Domain\Administrator\AdministratorId;
use ParkManager\Domain\Administrator\Exception\CannotDisableSuperAdministrator;
use ParkManager\Domain\EmailAddress;
use PHPUnit\Framework\TestCase;
use Rollerworks\Component\SplitToken\FakeSplitTokenFactory;
use Rollerworks\Component\SplitToken\SplitToken;

/**
 * @internal
 */
final class AdministratorTest extends TestCase
{
    private const ID1 = '930c3fd0-3bd1-11e7-bb9b-acdc32b58315';

    /** @var FakeSplitTokenFactory */
    private $splitTokenFactory;

    protected function setUp(): void
    {
        $this->splitTokenFactory = FakeSplitTokenFactory::instance();
    }

    /**
     * @test
     */
    public function registered(): void
    {
        $user = Administrator::register($id = AdministratorId::fromString(self::ID1), $email = new EmailAddress('Jane@example.com'), 'Janet Doe', 'wipPy');

        static::assertEquals($id, $user->getId());
        static::assertEquals($email, $user->getEmailAddress());
        static::assertEquals('Janet Doe', $user->getDisplayName());
        static::assertEquals('wipPy', $user->getPassword());
        static::assertTrue($user->isLoginEnabled());

        // Roles
        static::assertEquals(Administrator::DEFAULT_ROLES, $user->getRoles());
        static::assertTrue($user->hasRole('ROLE_ADMIN'));
        static::assertFalse($user->hasRole('ROLE_NOOP'));
    }

    /**
     * @test
     */
    public function change_email(): void
    {
        $user = $this->registerAdministrator();
        $user->changeEmail($email = new EmailAddress('Doh@example.com'));

        static::assertEquals($email, $user->getEmailAddress());
    }

    /**
     * @test
     */
    public function change_display_name(): void
    {
        $user = $this->registerAdministrator();
        $user->changeName('Jane Doe');

        static::assertEquals('Jane Doe', $user->getDisplayName());
    }

    /**
     * @test
     */
    public function disable_access(): void
    {
        $user = $this->registerAdministrator();
        $user->disableLogin();

        static::assertFalse($user->isLoginEnabled());
    }

    /**
     * @test
     */
    public function enable_access_after_disabled(): void
    {
        $user = $this->registerAdministrator();
        $user->disableLogin();
        $user->enableLogin();

        static::assertTrue($user->isLoginEnabled());
    }

    /**
     * @test
     */
    public function cannot_disable_access_when_super_admin(): void
    {
        $user = $this->registerAdministrator();
        $user->addRole('ROLE_SUPER_ADMIN');

        $this->expectException(CannotDisableSuperAdministrator::class);

        $user->disableLogin();
    }

    /**
     * @test
     */
    public function change_password(): void
    {
        $user = $this->registerAdministrator();

        $user->changePassword('security-is-null');

        static::assertEquals('security-is-null', $user->getPassword());
    }

    /**
     * @test
     */
    public function password_cannot_be_empty_when_string(): void
    {
        $user = $this->registerAdministrator();

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Password can only null or a non-empty string.');

        $user->changePassword('');
    }

    /**
     * @test
     */
    public function add_roles(): void
    {
        $user = $this->registerAdministrator();

        $user->addRole('ROLE_SUPER_ADMIN');
        $user->addRole('ROLE_SUPER_ADMIN'); // Ensure there're no duplicates

        static::assertEquals(['ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $user->getRoles());
        static::assertTrue($user->hasRole('ROLE_ADMIN'));
        static::assertTrue($user->hasRole('ROLE_SUPER_ADMIN'));
    }

    /**
     * @test
     */
    public function remove_role(): void
    {
        $user = $this->registerAdministrator();
        $user->addRole('ROLE_SUPER_ADMIN');

        $user->removeRole('ROLE_SUPER_ADMIN');

        static::assertEquals(Administrator::DEFAULT_ROLES, $user->getRoles());
        static::assertTrue($user->hasRole('ROLE_ADMIN'));
        static::assertFalse($user->hasRole('ROLE_SUPER_ADMIN'));
    }

    /**
     * @test
     */
    public function cannot_remove_default_role(): void
    {
        $user = $this->registerAdministrator();

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Cannot remove default role "ROLE_ADMIN".');

        $user->removeRole('ROLE_ADMIN');
    }

    /**
     * @test
     */
    public function request_password_reset(): void
    {
        $token = $this->createTimeLimitedSplitToken(new DateTimeImmutable('+ 5 minutes UTC'));

        $user = $this->registerAdministrator('pass-my-word');
        $user->requestPasswordReset($token);

        static::assertEquals($token->toValueHolder(), $user->getPasswordResetToken());
    }

    /**
     * @test
     */
    public function changes_password_when_token_is_correct(): void
    {
        $token = $this->createTimeLimitedSplitToken(new DateTimeImmutable('+ 5 minutes UTC'));
        $user = $this->registerAdministrator('pass-my-word');

        $user->requestPasswordReset($token);

        static::assertTrue($user->confirmPasswordReset($token2 = $this->getTokenString($token), 'new-password'));
        static::assertNull($user->getPasswordResetToken());
        static::assertFalse($user->confirmPasswordReset($token2, 'new2-password'));
    }

    /**
     * @test
     */
    public function password_reset_is_rejected_for_invalid_token(): void
    {
        $correctToken = $this->createTimeLimitedSplitToken(new DateTimeImmutable('+ 5 minutes UTC'));
        $invalidToken = $this->generateSecondToken();

        $user = $this->registerAdministrator('pass-my-word');

        $user->requestPasswordReset($correctToken);

        // Second attempt is prohibited, so try a second time (with correct token)!
        static::assertFalse($user->confirmPasswordReset($invalidToken, 'new-password'));
        static::assertNull($user->getPasswordResetToken());

        static::assertFalse($user->confirmPasswordReset($correctToken, 'new-password'));
    }

    /**
     * @test
     */
    public function password_reset_is_rejected_when_no_token_was_set(): void
    {
        $user = $this->registerAdministrator('pass-my-word');

        static::assertFalse($user->confirmPasswordReset($this->splitTokenFactory->generate(), 'new-password'));
    }

    /** @test */
    public function password_reset_is_rejected_when_token_has_expired(): void
    {
        $token = $this->createTimeLimitedSplitToken(new DateTimeImmutable('- 5 minutes UTC'));
        $user = $this->registerAdministrator('pass-my-word');
        $user->requestPasswordReset($token);

        static::assertFalse($user->confirmPasswordReset($token, 'new-password'));
        static::assertNull($user->getPasswordResetToken());
    }

    private function registerAdministrator(?string $password = null): Administrator
    {
        $administrator = Administrator::register(
            $id = AdministratorId::fromString(self::ID1),
            $email = new EmailAddress('Jane@example.com'),
            'Janet Doe'
        );
        $administrator->changePassword($password);

        return $administrator;
    }

    private function getTokenString(SplitToken $token): SplitToken
    {
        return $this->splitTokenFactory->fromString($token->token()->getString());
    }

    private function createTimeLimitedSplitToken($expiresAt): SplitToken
    {
        return $this->splitTokenFactory->generate()->expireAt($expiresAt);
    }

    private function generateSecondToken(): SplitToken
    {
        return FakeSplitTokenFactory::instance(\str_repeat('na', SplitToken::TOKEN_CHAR_LENGTH))->generate();
    }
}
