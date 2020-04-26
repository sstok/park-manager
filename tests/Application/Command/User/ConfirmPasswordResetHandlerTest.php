<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\User;

use ParkManager\Application\Command\User\ConfirmPasswordReset;
use ParkManager\Application\Command\User\ConfirmPasswordResetHandler;
use ParkManager\Domain\Exception\PasswordResetTokenNotAccepted;
use ParkManager\Domain\User\User;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use PHPUnit\Framework\TestCase;
use Rollerworks\Component\SplitToken\FakeSplitTokenFactory;
use Rollerworks\Component\SplitToken\SplitToken;

/**
 * @internal
 */
final class ConfirmPasswordResetHandlerTest extends TestCase
{
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
    public function handle_password_reset_confirmation(): void
    {
        $user = UserRepositoryMock::createUser();
        $user->requestPasswordReset($this->fullToken);
        $repository = new UserRepositoryMock([$user]);

        $handler = new ConfirmPasswordResetHandler($repository);
        $handler(new ConfirmPasswordReset($this->token, 'new-password'));

        $repository->assertEntitiesWereSaved();
        $repository->assertHasEntity($user->id->toString(), static function (User $user): void {
            self::assertEquals('new-password', $user->password);
            self::assertNull($user->passwordResetToken);
        });
    }

    /** @test */
    public function it_handles_password_reset_confirmation_with_failure(): void
    {
        $user = UserRepositoryMock::createUser();
        $user->requestPasswordReset($this->fullToken);
        $repository = new UserRepositoryMock([$user]);

        $handler = new ConfirmPasswordResetHandler($repository);

        try {
            $invalidToken = FakeSplitTokenFactory::instance()->fromString(FakeSplitTokenFactory::SELECTOR . \str_rot13(FakeSplitTokenFactory::VERIFIER));
            $handler(new ConfirmPasswordReset($invalidToken, 'my-password'));
        } catch (PasswordResetTokenNotAccepted $e) {
            $repository->assertHasEntity($user->id->toString(), static function (User $user): void {
                self::assertNull($user->passwordResetToken);
            });
        }
    }

    /** @test */
    public function it_handles_password_reset_confirmation_with_no_result(): void
    {
        $user = UserRepositoryMock::createUser();
        $repository = new UserRepositoryMock([$user]);

        $handler = new ConfirmPasswordResetHandler($repository);

        try {
            $handler(new ConfirmPasswordReset($this->token, 'my-password'));
        } catch (PasswordResetTokenNotAccepted $e) {
            $repository->assertNoEntitiesWereSaved();
        }
    }
}
