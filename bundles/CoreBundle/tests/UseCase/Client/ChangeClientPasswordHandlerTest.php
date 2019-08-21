<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Tests\UseCase\Client;

use ParkManager\Bundle\CoreBundle\UseCase\Client\ChangeClientPassword;
use ParkManager\Bundle\CoreBundle\UseCase\Client\ChangeClientPasswordHandler;
use ParkManager\Bundle\CoreBundle\Model\Client\Event\ClientPasswordWasChanged;
use ParkManager\Bundle\CoreBundle\Test\Domain\Repository\ClientRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ChangeClientPasswordHandlerTest extends TestCase
{
    /** @test */
    public function it_changes_password(): void
    {
        $client     = ClientRepositoryMock::createClient();
        $repository = new ClientRepositoryMock([$client]);

        $handler = new ChangeClientPasswordHandler($repository);
        $handler(new ChangeClientPassword($client->id()->toString(), 'new-password'));

        $repository->assertEntitiesWereSaved();
        $repository->assertHasEntityWithEvents(
            $client->id(),
            [
                new ClientPasswordWasChanged($client->id(), 'new-password'),
            ]
        );
    }

    /** @test */
    public function it_changes_password_to_null(): void
    {
        $client     = ClientRepositoryMock::createClient();
        $repository = new ClientRepositoryMock([$client]);

        $handler = new ChangeClientPasswordHandler($repository);
        $handler(new ChangeClientPassword($client->id()->toString(), null));

        $repository->assertEntitiesWereSaved();
        $repository->assertHasEntityWithEvents(
            $client->id(),
            [
                new ClientPasswordWasChanged($client->id(), null),
            ]
        );
    }
}
