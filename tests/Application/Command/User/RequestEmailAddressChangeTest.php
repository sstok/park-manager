<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\User;

use DateTimeImmutable;
use ParkManager\Application\Command\User\RequestEmailAddressChange;
use ParkManager\Application\Command\User\RequestEmailAddressChangeHandler;
use ParkManager\Application\Mailer\User\EmailAddressChangeRequestMailer;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\User\Exception\EmailAddressAlreadyInUse;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Rollerworks\Component\SplitToken\FakeSplitTokenFactory;
use Rollerworks\Component\SplitToken\SplitToken;

/**
 * @internal
 */
final class RequestEmailAddressChangeTest extends TestCase
{
    use ProphecyTrait;

    private const USER_ID = '01dd5964-5426-11e7-be03-acbc32b58315';

    /** @test */
    public function it_handles_email_address_change_request(): void
    {
        $handler = new RequestEmailAddressChangeHandler(
            $repository = new UserRepositoryMock([UserRepositoryMock::createUser()]),
            $this->createConfirmationMailer('John2@example.com'),
            FakeSplitTokenFactory::instance()
        );

        $handler(RequestEmailAddressChange::with(UserRepositoryMock::USER_ID1, 'John2@example.com'));

        $repository->assertEntityWasSavedThat(UserRepositoryMock::USER_ID1, static function (User $user): bool {
            $token = $user->emailAddressChangeToken;

            self::assertSame(['email' => 'John2@example.com'], $token->metadata());
            self::assertFalse($token->isExpired(new DateTimeImmutable('+ 5 seconds')));
            self::assertTrue($token->isExpired(new DateTimeImmutable('+ 3700 seconds')));

            return true;
        });
    }

    /** @test */
    public function it_handles_email_address_change_request_with_different_label(): void
    {
        $handler = new RequestEmailAddressChangeHandler(
            $repository = new UserRepositoryMock([UserRepositoryMock::createUser()]),
            $this->createConfirmationMailer('John2+spam@example.com'),
            FakeSplitTokenFactory::instance()
        );

        $handler(RequestEmailAddressChange::with(UserRepositoryMock::USER_ID1, 'John2+spam@example.com'));

        $repository->assertEntityWasSavedThat(UserRepositoryMock::USER_ID1, static function (User $user): bool {
            $token = $user->emailAddressChangeToken;

            self::assertSame(['email' => 'John2+spam@example.com'], $token->metadata());
            self::assertFalse($token->isExpired(new DateTimeImmutable('+ 5 seconds')));
            self::assertTrue($token->isExpired(new DateTimeImmutable('+ 3700 seconds')));

            return true;
        });
    }

    /** @test */
    public function it_handles_email_address_change_request_with_email_address_already_in_use(): void
    {
        $handler = new RequestEmailAddressChangeHandler(
            new UserRepositoryMock([
                UserRepositoryMock::createUser('janE@example.com'),
                UserRepositoryMock::createUser('John2@example.com', self::USER_ID),
            ]),
            $this->expectNoConfirmationIsSendMailer(),
            FakeSplitTokenFactory::instance()
        );

        $this->expectExceptionObject(
            new EmailAddressAlreadyInUse(
                UserId::fromString(UserRepositoryMock::USER_ID1),
                new EmailAddress('janE@example.com')
            )
        );

        $handler(RequestEmailAddressChange::with(self::USER_ID, 'janE@example.com'));
    }

    private function createConfirmationMailer(string $email): EmailAddressChangeRequestMailer
    {
        $confirmationMailerProphecy = $this->prophesize(EmailAddressChangeRequestMailer::class);
        $confirmationMailerProphecy->send(
            $email,
            Argument::that(
                static fn (SplitToken $splitToken): bool => $splitToken->token()->getString() !== ''
            )
        )->shouldBeCalledTimes(1);

        return $confirmationMailerProphecy->reveal();
    }

    private function expectNoConfirmationIsSendMailer(): EmailAddressChangeRequestMailer
    {
        $confirmationMailerProphecy = $this->prophesize(EmailAddressChangeRequestMailer::class);
        $confirmationMailerProphecy->send(Argument::any(), Argument::any())->shouldNotBeCalled();

        return $confirmationMailerProphecy->reveal();
    }
}
