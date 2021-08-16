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
use ParkManager\Application\Command\User\ChangeAvatar;
use ParkManager\Application\Command\User\ChangeAvatarHandler;
use ParkManager\Domain\User\UserId;
use ParkManager\Tests\Mock\Domain\UserRepositoryMock;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

/**
 * @internal
 */
final class ChangeAvatarHandlerTest extends TestCase
{
    /** @test */
    public function it_stores_an_avatar(): void
    {
        $userRepository = new UserRepositoryMock([UserRepositoryMock::createUser()]);
        $filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $handler = new ChangeAvatarHandler($userRepository, $filesystem);

        $file = __DIR__ . '/../../../Fixtures/avatar-test-min.jpg';
        $handler(new ChangeAvatar(UserId::fromString(UserRepositoryMock::USER_ID1), new SplFileInfo($file)));

        self::assertTrue($filesystem->fileExists(UserRepositoryMock::USER_ID1 . '.jpg'));
        self::assertStringEqualsFile($file, $filesystem->read(UserRepositoryMock::USER_ID1 . '.jpg'));
    }
}
