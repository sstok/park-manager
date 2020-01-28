<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\User;

use ParkManager\Domain\User\User;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use ParkManager\Application\Command\User\ChangeUserPassword;
use ParkManager\Application\Command\User\ChangePasswordHandler;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class ChangePasswordHandlerTest extends TestCase
{
    /** @test */
    public function it_changes_password(): void
    {
        $user = UserRepositoryMock::createUser();
        $repository = new UserRepositoryMock([$user]);

        $handler = new ChangePasswordHandler($repository, $this->createMock(EventDispatcherInterface::class));
        $handler(new ChangeUserPassword($id = $user->getId()->toString(), 'new-password'));

        $repository->assertEntitiesWereSaved();

        $repository->assertHasEntity($id, static function (User $user): void {
            self::assertEquals('new-password', $user->getPassword());
        });
    }
}
