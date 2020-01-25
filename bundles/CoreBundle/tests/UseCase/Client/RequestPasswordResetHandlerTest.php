<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Tests\UseCase\Client;

use DateTimeImmutable;
use ParkManager\Bundle\CoreBundle\Mailer\Client\PasswordResetMailer;
use ParkManager\Bundle\CoreBundle\Model\Client\Client;
use ParkManager\Bundle\CoreBundle\Test\Model\Repository\ClientRepositoryMock;
use ParkManager\Bundle\CoreBundle\UseCase\Client\RequestPasswordReset;
use ParkManager\Bundle\CoreBundle\UseCase\Client\RequestPasswordResetHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Rollerworks\Component\SplitToken\FakeSplitTokenFactory;

/**
 * @internal
 */
final class RequestPasswordResetHandlerTest extends TestCase
{
    /** @var FakeSplitTokenFactory */
    private $tokenFactory;

    protected function setUp(): void
    {
        $this->tokenFactory = FakeSplitTokenFactory::instance();
    }

    /** @test */
    public function handle_reset_request(): void
    {
        $repository = new ClientRepositoryMock([$client = ClientRepositoryMock::createClient()]);

        $handler = new RequestPasswordResetHandler($repository, $this->tokenFactory, $this->expectMailIsSend($client), 120);
        $handler(new RequestPasswordReset('Jane@example.com'));

        $repository->assertHasEntity(
            $client->getId(),
            static function (Client $entity): void {
                $valueHolder = $entity->getPasswordResetToken();
                self::assertFalse($valueHolder->isExpired(new DateTimeImmutable('+ 120 seconds')));
                self::assertTrue($valueHolder->isExpired(new DateTimeImmutable('+ 125 seconds')));
            }
        );
    }

    private function expectMailIsSend(Client $client): PasswordResetMailer
    {
        $mailerProphecy = $this->prophesize(PasswordResetMailer::class);
        $mailerProphecy->send($client->getEmail(), Argument::any())->shouldBeCalled();

        return $mailerProphecy->reveal();
    }

    /** @test */
    public function reset_request_already_set_will_not_store(): void
    {
        $client = ClientRepositoryMock::createClient();
        $client->requestPasswordReset($this->tokenFactory->generate());
        $repository = new ClientRepositoryMock([$client]);

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
        $repository = new ClientRepositoryMock();

        $handler = new RequestPasswordResetHandler($repository, $this->tokenFactory, $this->expectMailIsNotSend());
        $handler(new RequestPasswordReset('Jane@example.com'));

        $repository->assertNoEntitiesWereSaved();
    }
}
