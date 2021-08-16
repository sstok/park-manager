<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Application\Command\User;

use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use ParkManager\Application\Command\User\RemoveAvatar;
use ParkManager\Application\Command\User\RemoveAvatarHandler;
use ParkManager\Domain\User\UserId;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class RemoveAvatarHandlerTest extends TestCase
{
    /** @test */
    public function it_removes_an_existing_avatar(): void
    {
        $userRepository = new UserRepositoryMock([UserRepositoryMock::createUser()]);
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $handler = new RemoveAvatarHandler($userRepository, $filesystem);

        $filesystem->write(UserRepositoryMock::USER_ID1 . '.jpg', file_get_contents(__DIR__ . '/../../../Fixtures/avatar-test-min.jpg'));

        $handler(new RemoveAvatar(UserId::fromString(UserRepositoryMock::USER_ID1)));

        self::assertFalse($filesystem->fileExists(UserRepositoryMock::USER_ID1 . '.jpg'));
    }

    /** @test */
    public function it_removes_an_avatar_with_missing_file(): void
    {
        $userRepository = new UserRepositoryMock([UserRepositoryMock::createUser()]);
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $handler = new RemoveAvatarHandler($userRepository, $filesystem);

        $handler(new RemoveAvatar(UserId::fromString(UserRepositoryMock::USER_ID1)));

        self::assertFalse($filesystem->fileExists(UserRepositoryMock::USER_ID1 . '.jpg'));
    }
}
