<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\User;

use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use ParkManager\Application\Command\User\DeleteRegistration;
use ParkManager\Application\Command\User\DeleteRegistrationHandler;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DeleteRegistrationHandlerTest extends TestCase
{
    /** @test */
    public function it_deletes_a_user_registration(): void
    {
        $repository = new UserRepositoryMock([$user = UserRepositoryMock::createUser()]);

        $handler = new DeleteRegistrationHandler($repository);
        $handler(new DeleteRegistration(UserRepositoryMock::USER_ID1));

        $repository->assertEntitiesWereRemoved([$user]);
    }
}
