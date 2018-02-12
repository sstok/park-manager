<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Component\User\Tests\Model;

use Assert\AssertionFailedException;
use ParkManager\Component\Model\Test\EventsRecordingEntityAssertionTrait;
use ParkManager\Component\Model\Tests\ObjectHydrationAssertTrait;
use ParkManager\Component\Security\Token\SplitToken;
use ParkManager\Component\User\Model\Event\UserPasswordWasChanged;
use ParkManager\Component\User\Model\User;
use ParkManager\Component\User\Model\UserId;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class UserTest extends TestCase
{
    use EventsRecordingEntityAssertionTrait;
    use ObjectHydrationAssertTrait;

    private const ID1 = '930c3fd0-3bd1-11e7-bb9b-acdc32b58315';

    /** @test */
    public function its_constructable()
    {
        $user = new UserImplementation($id = UserId::fromString(self::ID1), 'John@example.com', 'john@example.com');

        self::assertEquals($id, $user->id());
        self::assertEquals('John@example.com', $user->email());
        self::assertEquals('john@example.com', $user->canonicalEmail());
        self::assertEquals([User::DEFAULT_ROLE], $user->roles());
        self::assertTrue($user->hasRole(User::DEFAULT_ROLE));
        self::assertFalse($user->hasRole('ROLE_NOOP'));
        self::assertTrue($user->isEnabled());
        self::assertNull($user->password());
    }

    /** @test */
    public function its_id_is_correct_after_hydration()
    {
        self::assertHydratedObjectValueEquals(UserImplementation::class, self::ID1, UserId::fromString(self::ID1));
    }

    /** @test */
    public function it_can_changes_email()
    {
        $user = $this->createUser();

        $user->changeEmail('Doh@example.com', 'doh@example.com');

        self::assertEquals('Doh@example.com', $user->email());
        self::assertEquals('doh@example.com', $user->canonicalEmail());
        self::assertNoDomainEvents($user);
    }

    /** @test */
    public function it_can_be_disabled()
    {
        $user = $this->createUser();

        $user->disable();

        self::assertFalse($user->isEnabled());
        self::assertNoDomainEvents($user);
    }

    /** @test */
    public function it_can_be_enabled_after_being_disabled()
    {
        $user = $this->createUser();
        $user->disable();

        $user->enable();

        self::assertTrue($user->isEnabled());
        self::assertNoDomainEvents($user);
    }

    /** @test */
    public function it_allows_setting_a_password()
    {
        $user = $this->createUser();

        $user->changePassword('security-is-null');

        self::assertEquals('security-is-null', $user->password());
        self::assertDomainEvents($user, [new UserPasswordWasChanged($user->id())]);
    }

    /** @test */
    public function it_allows_setting_password_to_null()
    {
        $user = $this->createUser('security-is-null');
        $user->changePassword(null);

        self::assertNull($user->password());
        self::assertDomainEvents($user, [new UserPasswordWasChanged($user->id())]);
    }

    /** @test */
    public function it_checks_password_is_nul_non_empty_string()
    {
        $user = $this->createUser();

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Password can only null or a non-empty string.');

        $user->changePassword('');
    }

    /** @test */
    public function it_allows_adding_roles()
    {
        $user = $this->createUser();

        $user->addRole('ROLE_SUPER_ADMIN');
        $user->addRole('ROLE_SUPER_ADMIN'); // Ensure there're no duplicates

        self::assertEquals([User::DEFAULT_ROLE, 'ROLE_SUPER_ADMIN'], $user->roles());
        self::assertTrue($user->hasRole(User::DEFAULT_ROLE));
        self::assertTrue($user->hasRole('ROLE_SUPER_ADMIN'));
        self::assertNoDomainEvents($user);
    }

    /** @test */
    public function it_allows_removing_roles()
    {
        $user = $this->createUser();
        $user->addRole('ROLE_SUPER_ADMIN');

        $user->removeRole('ROLE_SUPER_ADMIN');

        self::assertEquals([User::DEFAULT_ROLE], $user->roles());
        self::assertTrue($user->hasRole(User::DEFAULT_ROLE));
        self::assertFalse($user->hasRole('ROLE_SUPER_ADMIN'));
        self::assertNoDomainEvents($user);
    }

    /** @test */
    public function it_disallows_removing_default_role()
    {
        $user = $this->createUser();

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Cannot remove default role "'.User::DEFAULT_ROLE.'".');

        $user->removeRole(User::DEFAULT_ROLE);
    }

    /** @test */
    public function it_sets_emailAddress_confirmation_token()
    {
        $token = SplitToken::generate(self::ID1);
        $expiration = new \DateTimeImmutable('+ 5 minutes UTC');
        $user = $this->createUser();

        $tokenWasSet = $user->setConfirmationOfEmailAddressChange(
            'Doh@example.com',
            'doh@example.com',
            $token->toValueHolder($expiration)
        );

        self::assertTrue($tokenWasSet);
        self::assertEquals('john@example.com', $user->email());
        self::assertEquals('john@example.com', $user->canonicalEmail());
    }

    /** @test */
    public function it_does_not_set_emailAddress_confirmation_token_when_already_set_with_same_information()
    {
        $token = SplitToken::generate(self::ID1);
        $expiration = new \DateTimeImmutable('+ 5 minutes UTC');
        $user = $this->createUser();

        $user->setConfirmationOfEmailAddressChange(
            'Doh@example.com',
            'doh@example.com',
            $token->toValueHolder($expiration)
        );

        $tokenWasSet = $user->setConfirmationOfEmailAddressChange(
            'Doh@example.com',
            'doh@example.com',
            $token->toValueHolder($expiration)
        );

        self::assertFalse($tokenWasSet);
        self::assertEquals('john@example.com', $user->email());
        self::assertEquals('john@example.com', $user->canonicalEmail());
    }

    /** @test */
    public function it_changes_emailAddress_when_confirmation_token_is_correct()
    {
        $token = SplitToken::generate(self::ID1);
        $expiration = new \DateTimeImmutable('+ 5 minutes UTC');
        $user = $this->createUser();
        $user->setConfirmationOfEmailAddressChange(
            'Doh@example.com',
            'doh@example.com',
            $token->toValueHolder($expiration)
        );

        // Second usage is prohibited, so try a second time.
        $changeWasAccepted = $user->confirmEmailAddressChange($token);
        $changeWasAcceptedSecond = $user->confirmEmailAddressChange($token);

        self::assertTrue($changeWasAccepted);
        self::assertFalse($changeWasAcceptedSecond);
        self::assertEquals('Doh@example.com', $user->email());
        self::assertEquals('doh@example.com', $user->canonicalEmail());
    }

    /** @test */
    public function it_does_not_change_emailAddress_when_confirmation_token_is_invalid()
    {
        $token = SplitToken::generate(self::ID1);
        $token2 = SplitToken::generate('930c3fd0-3bd1-11e7-bb9b-acdc32b58320');
        $expiration = new \DateTimeImmutable('+ 5 minutes UTC');
        $user = $this->createUser();
        $user->setConfirmationOfEmailAddressChange(
            'Doh@example.com',
            'doh@example.com',
            $token->toValueHolder($expiration)
        );

        // Second attempt is prohibited, so try a second time (with correct token)!
        $changeWasAccepted = $user->confirmEmailAddressChange($token2);
        $changeWasAcceptedSecond = $user->confirmEmailAddressChange($token);

        self::assertFalse($changeWasAccepted);
        self::assertFalse($changeWasAcceptedSecond);
        self::assertEquals('john@example.com', $user->email());
        self::assertEquals('john@example.com', $user->canonicalEmail());
    }

    /** @test */
    public function it_does_not_change_emailAddress_when_confirmation_token_is_unset()
    {
        $token = SplitToken::generate(self::ID1);
        $user = $this->createUser();

        $changeWasAccepted = $user->confirmEmailAddressChange($token);

        self::assertFalse($changeWasAccepted);
        self::assertEquals('john@example.com', $user->email());
        self::assertEquals('john@example.com', $user->canonicalEmail());
    }

    /** @test */
    public function it_does_not_change_emailAddress_when_confirmation_token_has_expired()
    {
        $token = SplitToken::generate(self::ID1);
        $expiration = new \DateTimeImmutable('- 5 minutes');
        $user = $this->createUser();
        $user->setConfirmationOfEmailAddressChange(
            'Doh@example.com',
            'doh@example.com',
            $token->toValueHolder($expiration)
        );

        $changeWasAccepted = $user->confirmEmailAddressChange($token);

        self::assertFalse($changeWasAccepted);
        self::assertEquals('john@example.com', $user->email());
        self::assertEquals('john@example.com', $user->canonicalEmail());
    }

    /** @test */
    public function it_sets_passwordReset_confirmation_token()
    {
        $token = SplitToken::generate(self::ID1);
        $expiration = new \DateTimeImmutable('+ 5 minutes UTC');
        $user = $this->createUser('pass-my-word');

        $tokenWasSet = $user->setPasswordResetToken(
            $token->toValueHolder($expiration)
        );

        self::assertTrue($tokenWasSet);
    }

    /** @test */
    public function it_does_not_set_passwordReset_confirmation_token_when_already_set_with_and_not_expired()
    {
        $token = SplitToken::generate(self::ID1);
        $expiration = new \DateTimeImmutable('+ 5 minutes UTC');
        $user = $this->createUser('pass-my-word');

        $user->setPasswordResetToken($token->toValueHolder($expiration));
        $tokenWasSet = $user->setPasswordResetToken($token->toValueHolder($expiration));

        self::assertFalse($tokenWasSet);
    }

    /** @test */
    public function it_changes_password_when_reset_confirmation_token_is_correct()
    {
        $token = SplitToken::generate(self::ID1);
        $expiration = new \DateTimeImmutable('+ 5 minutes UTC');
        $user = $this->createUser('pass-my-word');
        $user->setPasswordResetToken($tokenHolder = $token->toValueHolder($expiration));

        // Second usage is prohibited, so try a second time.
        $currentToken = $user->passwordResetToken();
        $changeWasAccepted = $user->confirmPasswordReset($token, 'new-password');
        $changeWasAcceptedSecond = $user->confirmPasswordReset($token, 'new2-password');
        $tokenAfter = $user->passwordResetToken();

        self::assertTrue($changeWasAccepted);
        self::assertFalse($changeWasAcceptedSecond);
        self::assertEquals($tokenHolder, $currentToken);
        self::assertNull($tokenAfter);
        self::assertEquals('new-password', $user->password());
        self::assertDomainEvents($user, [new UserPasswordWasChanged($user->id())]);
    }

    /** @test */
    public function it_does_not_change_password_when_reset_confirmation_token_is_invalid()
    {
        $token = SplitToken::generate(self::ID1);
        $token2 = SplitToken::generate('930c3fd0-3bd1-11e7-bb9b-acdc32b58320');
        $expiration = new \DateTimeImmutable('+ 5 minutes UTC');
        $user = $this->createUser('pass-my-word');
        $user->setPasswordResetToken($token->toValueHolder($expiration));

        // Second attempt is prohibited, so try a second time (with correct token)!
        $changeWasAccepted = $user->confirmPasswordReset($token2, 'new-password');
        $changeWasAcceptedSecond = $user->confirmPasswordReset($token, 'new-password');

        self::assertFalse($changeWasAccepted);
        self::assertFalse($changeWasAcceptedSecond);
        self::assertEquals('pass-my-word', $user->password());
    }

    /** @test */
    public function it_does_not_change_password_when_reset_confirmation_token_is_unset()
    {
        $token = SplitToken::generate('930c3fd0-3bd1-11e7-bb9b-acdc32b58320');
        $user = $this->createUser('pass-my-word');

        $changeWasAccepted = $user->confirmPasswordReset($token, 'new-password');

        self::assertFalse($changeWasAccepted);
        self::assertEquals('pass-my-word', $user->password());
    }

    /** @test */
    public function it_does_not_change_password_when_reset_confirmation_token_has_expired()
    {
        $token = SplitToken::generate(self::ID1);
        $expiration = new \DateTimeImmutable('- 5 minutes');
        $user = $this->createUser('pass-my-word');
        $user->setPasswordResetToken($token->toValueHolder($expiration));

        $changeWasAccepted = $user->confirmPasswordReset($token, 'new-password');

        self::assertFalse($changeWasAccepted);
        self::assertEquals('pass-my-word', $user->password());
    }

    private function createUser(string $password = null): UserImplementation
    {
        $user = new UserImplementation(UserId::fromString(self::ID1), 'john@example.com', 'john@example.com');
        $user->changePassword($password);

        // Clear events.
        $user->releaseEvents();

        return $user;
    }
}

class UserImplementation extends User
{
    public function __construct(UserId $id, string $email, string $canonicalEmail)
    {
        parent::__construct($id, $email, $canonicalEmail);
    }
}
