<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\User;

use Lifthill\Component\Common\Domain\Model\EmailAddress;
use ParkManager\Application\Command\User\ConfirmEmailAddressChange;
use ParkManager\Application\Command\User\ConfirmEmailAddressChangeHandler;
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
    private FakeSplitTokenFactory $splitTokenFactory;
    private SplitToken $fullToken;
    private SplitToken $token;

    protected function setUp(): void
    {
        $this->splitTokenFactory = new FakeSplitTokenFactory();
        $this->fullToken = $this->splitTokenFactory->generate();
        $this->token = $this->splitTokenFactory->fromString($this->fullToken->token()->getString());
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
            $user->id,
            static function (User $entity): void {
                self::assertEquals(new EmailAddress('janet@example.com'), $entity->email);
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
            $invalidToken = $this->splitTokenFactory->fromString(FakeSplitTokenFactory::SELECTOR . str_rot13(FakeSplitTokenFactory::VERIFIER));
            $handler(new ConfirmEmailAddressChange($invalidToken));

            self::fail('Exception was expected.');
        } catch (EmailChangeConfirmationRejected) {
            $repository->assertEntitiesWereSaved();
            $repository->assertHasEntity(
                $user->id,
                static function (User $entity): void {
                    self::assertEquals(new EmailAddress('janE@example.com'), $entity->email);
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
            $handler(new ConfirmEmailAddressChange((new FakeSplitTokenFactory('nananananananannnannanananannananna-batman'))->generate()));

            self::fail('Exception was expected.');
        } catch (EmailChangeConfirmationRejected) {
            $repository->assertNoEntitiesWereSaved();
        }
    }
}
