<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\User;

use ParkManager\Application\Command\User\ConfirmEmailAddressChange;
use ParkManager\Application\Command\User\ConfirmEmailAddressChangeHandler;
use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\User\Exception\EmailChangeConfirmationRejected;
use ParkManager\Domain\User\User;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use PHPUnit\Framework\TestCase;
use Rollerworks\Component\SplitToken\FakeSplitTokenFactory;
use Rollerworks\Component\SplitToken\SplitToken;

/**
 * @internal
 */
final class ConfirmEmailAddressChangeHandlerTest extends TestCase
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
    public function it_handles_email_address_change_confirmation(): void
    {
        $user = UserRepositoryMock::createUser();
        $user->requestEmailChange(new EmailAddress('janet@example.com'), $this->fullToken);
        $repository = new UserRepositoryMock([$user]);

        $handler = new ConfirmEmailAddressChangeHandler($repository);
        $handler(new ConfirmEmailAddressChange($this->token));

        $repository->assertEntitiesWereSaved();
        $repository->assertHasEntity(
            $user->getId(),
            static function (User $entity): void {
                self::assertEquals(new EmailAddress('janet@example.com'), $entity->getEmail());
            }
        );
    }

    /** @test */
    public function it_handles_email_address_change_confirmation_with_failure(): void
    {
        $user = UserRepositoryMock::createUser();
        $user->requestEmailChange(new EmailAddress('janet@example.com'), $this->fullToken);
        $repository = new UserRepositoryMock([$user]);

        $handler = new ConfirmEmailAddressChangeHandler($repository);

        try {
            $invalidToken = FakeSplitTokenFactory::instance()->fromString(FakeSplitTokenFactory::SELECTOR . \str_rot13(FakeSplitTokenFactory::VERIFIER));
            $handler(new ConfirmEmailAddressChange($invalidToken));

            static::fail('Exception was expected.');
        } catch (EmailChangeConfirmationRejected $e) {
            $repository->assertEntitiesWereSaved();
            $repository->assertHasEntity(
                $user->getId(),
                static function (User $entity): void {
                    self::assertEquals(new EmailAddress('janE@example.com'), $entity->getEmail());
                }
            );
        }
    }

    /** @test */
    public function it_handles_email_address_change_confirmation_with_no_result(): void
    {
        $user = UserRepositoryMock::createUser();
        $repository = new UserRepositoryMock([$user]);

        $handler = new ConfirmEmailAddressChangeHandler($repository);

        try {
            $handler(new ConfirmEmailAddressChange(FakeSplitTokenFactory::instance('nananananananannnannanananannananna-batman')->generate()));

            static::fail('Exception was expected.');
        } catch (EmailChangeConfirmationRejected $e) {
            $repository->assertNoEntitiesWereSaved();
        }
    }
}
