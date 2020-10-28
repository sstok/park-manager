<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\User;

use DateTimeImmutable;
use ParkManager\Application\Command\User\RequestPasswordReset;
use ParkManager\Application\Command\User\RequestPasswordResetHandler;
use ParkManager\Application\Mailer\User\PasswordResetMailer;
use ParkManager\Domain\User\User;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Rollerworks\Component\SplitToken\FakeSplitTokenFactory;

/**
 * @internal
 */
final class RequestPasswordResetHandlerTest extends TestCase
{
    private FakeSplitTokenFactory $tokenFactory;

    protected function setUp(): void
    {
        $this->tokenFactory = FakeSplitTokenFactory::instance();
    }

    /** @test */
    public function handle_reset_request(): void
    {
        $repository = new UserRepositoryMock([$user = UserRepositoryMock::createUser()]);

        $handler = new RequestPasswordResetHandler($repository, $this->tokenFactory, $this->expectMailIsSend($user), 120);
        $handler(new RequestPasswordReset('Jane@example.com'));

        $repository->assertEntitiesCountWasSaved(1);
        $repository->assertHasEntity(
            $user->id,
            static function (User $entity): void {
                self::assertFalse($entity->passwordResetToken->isExpired(new DateTimeImmutable('+ 120 seconds')));
                self::assertTrue($entity->passwordResetToken->isExpired(new DateTimeImmutable('+ 125 seconds')));
            }
        );
    }

    private function expectMailIsSend(User $user): PasswordResetMailer
    {
        $mailerProphecy = $this->prophesize(PasswordResetMailer::class);
        $mailerProphecy->send($user->email, Argument::any())->shouldBeCalled();

        return $mailerProphecy->reveal();
    }

    /** @test */
    public function reset_request_already_set_will_not_store(): void
    {
        $user = UserRepositoryMock::createUser();
        $user->requestPasswordReset($this->tokenFactory->generate());
        $repository = new UserRepositoryMock([$user]);

        $handler = new RequestPasswordResetHandler($repository, $this->tokenFactory, $this->expectMailIsNotSend());
        $handler(new RequestPasswordReset('Jane@example.com'));

        $repository->assertNoEntitiesWereSaved();
    }

    private function expectMailIsNotSend(): PasswordResetMailer
    {
        $mailerProphecy = $this->prophesize(PasswordResetMailer::class);
        $mailerProphecy->send(Argument::any(), Argument::any())->shouldNotBeCalled();

        return $mailerProphecy->reveal();
    }

    /** @test */
    public function reset_request_with_no_existing_email_does_nothing(): void
    {
        $repository = new UserRepositoryMock();

        $handler = new RequestPasswordResetHandler($repository, $this->tokenFactory, $this->expectMailIsNotSend());
        $handler(new RequestPasswordReset('Jane@example.com'));

        $repository->assertNoEntitiesWereSaved();
    }
}
