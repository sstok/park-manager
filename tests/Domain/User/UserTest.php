<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\User;

use Assert\AssertionFailedException;
use DateTimeImmutable;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Exception\PasswordResetTokenNotAccepted;
use ParkManager\Domain\User\Exception\EmailChangeConfirmationRejected;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use PHPUnit\Framework\TestCase;
use Rollerworks\Component\SplitToken\FakeSplitTokenFactory;
use Rollerworks\Component\SplitToken\SplitToken;

/**
 * @internal
 */
final class UserTest extends TestCase
{
    private const ID1 = '930c3fd0-3bd1-11e7-bb9b-acdc32b58315';

    /** @var FakeSplitTokenFactory */
    private $splitTokenFactory;

    protected function setUp(): void
    {
        $this->splitTokenFactory = FakeSplitTokenFactory::instance();
    }

    /** @test */
    public function gets_registered(): void
    {
        $user = User::register(
            $id = UserId::fromString(self::ID1),
            $email = new EmailAddress('John@example.com'),
            'Jane Doe'
        );

        static::assertEquals($id, $user->getId());
        static::assertEquals($email, $user->getEmail());
    }

    /** @test */
    public function change_email(): void
    {
        $user = $this->registerUser();
        $user->changeEmail($email = new EmailAddress('Doh@example.com'));

        static::assertEquals($email, $user->getEmail());
    }

    private function registerUser(?string $password = null): User
    {
        $user = User::register(UserId::fromString(self::ID1), new EmailAddress('john@example.com'), 'Laural Doe');
        $user->changePassword($password);

        return $user;
    }

    /** @test */
    public function change_display_name(): void
    {
        $user = $this->registerUser();
        $user->changeName('Jenny');

        static::assertEquals('Jenny', $user->getDisplayName());
    }

    /** @test */
    public function disable_access(): void
    {
        $user = $this->registerUser();
        $user->disable();

        static::assertFalse($user->isEnabled());
    }

    /** @test */
    public function enable_access_after_disabled(): void
    {
        $user = $this->registerUser();
        $user->disable();
        $user->enable();

        static::assertTrue($user->isEnabled());
    }

    /** @test */
    public function change_password(): void
    {
        $user = $this->registerUser();

        $user->changePassword('security-is-null');

        static::assertEquals('security-is-null', $user->getPassword());
    }

    /** @test */
    public function password_cannot_be_empty_when_string(): void
    {
        $user = $this->registerUser();

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Password can only null or a non-empty string.');

        $user->changePassword('');
    }

    /** @test */
    public function request_email_change(): void
    {
        $token = $this->createTimeLimitedSplitToken(new DateTimeImmutable('+ 5 minutes UTC'));
        $user = $this->registerUser();

        static::assertTrue($user->requestEmailChange($email = new EmailAddress('Doh@example.com'), $token));
        static::assertEquals(new EmailAddress('john@example.com'), $user->getEmail());
    }

    private function createTimeLimitedSplitToken(DateTimeImmutable $expiresAt): SplitToken
    {
        return $this->splitTokenFactory->generate()->expireAt($expiresAt);
    }

    /** @test */
    public function ignores_email_change_token_when_already_set_with_same_information(): void
    {
        $token = $this->createTimeLimitedSplitToken(new DateTimeImmutable('+ 5 minutes UTC'));
        $user = $this->registerUser();

        static::assertTrue($user->requestEmailChange($email = new EmailAddress('Doh@example.com'), $token));
        static::assertFalse($user->requestEmailChange($email, $token));
    }

    /** @test */
    public function changes_email_when_confirmation_token_is_correct(): void
    {
        $token = $this->createTimeLimitedSplitToken(new DateTimeImmutable('+ 5 minutes UTC'));
        $user = $this->registerUser();
        $user->requestEmailChange($email = new EmailAddress('Doh@example.com'), $token);

        $user->confirmEmailChange($this->getTokenString($token));

        // Second usage is prohibited, so try a second time.
        $this->assertEmailChangeThrowsRejected($user, $token);

        static::assertEquals($email, $user->getEmail());
    }

    private function assertEmailChangeThrowsRejected(User $user, SplitToken $token): void
    {
        try {
            $user->confirmEmailChange($token);

            static::fail('EmailChangeConfirmationRejected was expected');
        } catch (EmailChangeConfirmationRejected $e) {
            $this->addToAssertionCount(1);
        }
    }

    private function getTokenString(SplitToken $token): SplitToken
    {
        return $this->splitTokenFactory->fromString($token->token()->getString());
    }

    /** @test */
    public function rejects_email_change_confirmation_when_token_is_invalid(): void
    {
        $correctToken = $this->createTimeLimitedSplitToken(new DateTimeImmutable('+ 5 minutes UTC'));
        $invalidToken = $this->generateSecondToken();

        $user = $this->registerUser();
        $user->requestEmailChange(new EmailAddress('Doh@example.com'), $correctToken);

        $this->assertEmailChangeThrowsRejected($user, $invalidToken);

        // Second attempt is prohibited, so try a second time (with correct token)!
        $this->assertEmailChangeThrowsRejected($user, $correctToken);

        static::assertEquals(new EmailAddress('john@example.com'), $user->getEmail());
    }

    private function generateSecondToken(): SplitToken
    {
        return FakeSplitTokenFactory::instance(\str_repeat('na', SplitToken::TOKEN_CHAR_LENGTH))->generate();
    }

    /** @test */
    public function rejects_email_change_confirmation_when_token_was_not_set(): void
    {
        $token = FakeSplitTokenFactory::instance()->generate();
        $user = $this->registerUser();

        $this->assertEmailChangeThrowsRejected($user, $token);
        static::assertEquals(new EmailAddress('john@example.com'), $user->getEmail());
    }

    /** @test */
    public function request_password_reset_confirmation_token(): void
    {
        $token = $this->createTimeLimitedSplitToken(new DateTimeImmutable('+ 5 minutes UTC'));
        $user = $this->registerUser('pass-my-word');

        static::assertTrue($user->requestPasswordReset($token));
    }

    /** @test */
    public function reject_password_reset_confirmation_when_token_already_set_with_and_not_expired(): void
    {
        $token = $this->createTimeLimitedSplitToken(new DateTimeImmutable('+ 5 minutes UTC'));
        $user = $this->registerUser('pass-my-word');

        static::assertTrue($user->requestPasswordReset($token));
        static::assertFalse($user->requestPasswordReset($token));
    }

    /** @test */
    public function changes_password_when_token_is_correct(): void
    {
        $token = $this->createTimeLimitedSplitToken(new DateTimeImmutable('+ 5 minutes UTC'));
        $user = $this->registerUser('pass-my-word');
        $user->requestPasswordReset($token);

        $user->confirmPasswordReset($token2 = $this->getTokenString($token), 'new-password');

        static::assertEquals('new-password', $user->getPassword());
        static::assertNull($user->getPasswordResetToken());
    }

    /** @test */
    public function password_reset_is_rejected_for_invalid_token(): void
    {
        $correctToken = $this->createTimeLimitedSplitToken(new DateTimeImmutable('+ 5 minutes UTC'));
        $invalidToken = $this->generateSecondToken();

        $user = $this->registerUser('pass-my-word');
        $user->requestPasswordReset($correctToken);

        $this->assertPasswordResetThrowsRejected($user, $invalidToken);

        // Second attempt is prohibited, so try a second time (with correct token)!
        $this->assertPasswordResetThrowsRejected($user, $correctToken);
    }

    private function assertPasswordResetThrowsRejected(User $user, SplitToken $token): void
    {
        try {
            $user->confirmPasswordReset($token, 'new-password');

            static::fail('PasswordResetConfirmationRejected was expected');
        } catch (PasswordResetTokenNotAccepted $e) {
            $this->addToAssertionCount(1);
        }
    }

    /** @test */
    public function password_reset_is_rejected_when_no_token_was_set(): void
    {
        $user = $this->registerUser('pass-my-word');

        $this->assertPasswordResetThrowsRejected($user, $this->splitTokenFactory->generate());
    }

    /** @test */
    public function password_reset_is_rejected_when_token_has_expired(): void
    {
        $token = $this->createTimeLimitedSplitToken(new DateTimeImmutable('- 5 minutes UTC'));
        $user = $this->registerUser('pass-my-word');
        $user->requestPasswordReset($token);

        $this->assertPasswordResetThrowsRejected($user, $token);

        static::assertEquals('pass-my-word', $user->getPassword());
        static::assertNull($user->getPasswordResetToken());
    }
}
