<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Tests\UseCase\Client;

use ParkManager\Bundle\CoreBundle\Model\Client\Event\ClientPasswordWasChanged;
use ParkManager\Bundle\CoreBundle\Model\Exception\PasswordResetTokenNotAccepted;
use ParkManager\Bundle\CoreBundle\Test\Domain\Repository\ClientRepositoryMock;
use ParkManager\Bundle\CoreBundle\UseCase\Client\ConfirmPasswordReset;
use ParkManager\Bundle\CoreBundle\UseCase\Client\ConfirmPasswordResetHandler;
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
        $client = ClientRepositoryMock::createClient();
        $client->requestPasswordReset($this->fullToken);
        $repository = new ClientRepositoryMock([$client]);

        $handler = new ConfirmPasswordResetHandler($repository);
        $handler(new ConfirmPasswordReset($this->token, 'my-password'));

        $repository->assertEntitiesWereSaved();
        $repository->assertHasEntityWithEvents(
            $client->getId(),
            [
                new ClientPasswordWasChanged($client->getId(), 'my-password'),
            ]
        );
    }

    /** @test */
    public function it_handles_password_reset_confirmation_with_failure(): void
    {
        $client = ClientRepositoryMock::createClient();
        $client->requestPasswordReset($this->fullToken);
        $repository = new ClientRepositoryMock([$client]);

        $handler = new ConfirmPasswordResetHandler($repository);

        try {
            $invalidToken = FakeSplitTokenFactory::instance()->fromString(FakeSplitTokenFactory::SELECTOR . \str_rot13(FakeSplitTokenFactory::VERIFIER));
            $handler(new ConfirmPasswordReset($invalidToken, 'my-password'));
        } catch (PasswordResetTokenNotAccepted $e) {
            $repository->assertEntitiesWereSaved([$client]);
        }
    }

    /** @test */
    public function it_handles_password_reset_confirmation_with_no_result(): void
    {
        $client = ClientRepositoryMock::createClient();
        $repository = new ClientRepositoryMock([$client]);

        $handler = new ConfirmPasswordResetHandler($repository);

        try {
            $handler(new ConfirmPasswordReset($this->token, 'my-password'));
        } catch (PasswordResetTokenNotAccepted $e) {
            $repository->assertHasEntityWithEvents($client->getId(), []);
        }
    }
}
