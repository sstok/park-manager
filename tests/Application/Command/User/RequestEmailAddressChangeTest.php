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
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Rollerworks\Component\SplitToken\FakeSplitTokenFactory;
use Rollerworks\Component\SplitToken\SplitToken;

/**
 * @internal
 */
final class RequestEmailAddressChangeTest extends TestCase
{
    private const USER_ID = '01dd5964-5426-11e7-be03-acbc32b58315';

    /** @var SplitToken */
    private $fullToken;

    /** @var SplitToken */
    private $token;

    protected function setUp(): void
    {
        $this->fullToken = FakeSplitTokenFactory::instance()->generate();
        $this->token = FakeSplitTokenFactory::instance()->fromString($this->fullToken->token()->getString());
    }

    /** @test */
    public function it_handles_email_address_change_request(): void
    {
        $handler = new RequestEmailAddressChangeHandler(
            $repository = new UserRepositoryMock([$user = UserRepositoryMock::createUser()]),
            $this->createConfirmationMailer('John2@example.com'),
            FakeSplitTokenFactory::instance()
        );

        $handler(new RequestEmailAddressChange(UserRepositoryMock::USER_ID1, 'John2@example.com'));

        $repository->assertEntitiesWereSaved();
        $token = $user->getEmailAddressChangeToken();
        self::assertEquals(['email' => 'John2@example.com'], $token->metadata());
        self::assertFalse($token->isExpired(new DateTimeImmutable('+ 5 seconds')));
        self::assertTrue($token->isExpired(new DateTimeImmutable('+ 3700 seconds')));
    }

    /** @test */
    public function it_handles_email_address_change_request_with_email_address_already_in_use(): void
    {
        $handler = new RequestEmailAddressChangeHandler(
            $repository = new UserRepositoryMock([
                UserRepositoryMock::createUser('janE@example.com'),
                $user2 = UserRepositoryMock::createUser('John2@example.com'),
            ]),
            $this->expectNoConfirmationIsSendMailer(),
            FakeSplitTokenFactory::instance()
        );

        $handler(new RequestEmailAddressChange(self::USER_ID, 'John2@example.com'));

        $repository->assertNoEntitiesWereSaved();
    }

    private function createConfirmationMailer(string $email): EmailAddressChangeRequestMailer
    {
        $confirmationMailerProphecy = $this->prophesize(EmailAddressChangeRequestMailer::class);
        $confirmationMailerProphecy->send(
            $email,
            Argument::that(
                static function (SplitToken $splitToken) {
                    return $splitToken->token()->getString() !== '';
                }
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
