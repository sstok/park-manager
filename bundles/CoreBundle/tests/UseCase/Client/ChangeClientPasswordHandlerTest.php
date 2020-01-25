<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Tests\UseCase\Client;

use ParkManager\Bundle\CoreBundle\Model\Client\Client;
use ParkManager\Bundle\CoreBundle\Test\Model\Repository\ClientRepositoryMock;
use ParkManager\Bundle\CoreBundle\UseCase\Client\ChangeClientPassword;
use ParkManager\Bundle\CoreBundle\UseCase\Client\ChangeClientPasswordHandler;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class ChangeClientPasswordHandlerTest extends TestCase
{
    /** @test */
    public function it_changes_password(): void
    {
        $client = ClientRepositoryMock::createClient();
        $repository = new ClientRepositoryMock([$client]);

        $handler = new ChangeClientPasswordHandler($repository, $this->createMock(EventDispatcherInterface::class));
        $handler(new ChangeClientPassword($id = $client->getId()->toString(), 'new-password'));

        $repository->assertEntitiesWereSaved();

        $repository->assertHasEntity($id, function (Client $client): void {
            self::assertEquals('new-password', $client->getPassword());
        });
    }
}
