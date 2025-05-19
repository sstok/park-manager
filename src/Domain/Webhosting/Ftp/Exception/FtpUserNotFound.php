<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Ftp\Exception;

use ParkManager\Domain\Exception\NotFoundException;
use ParkManager\Domain\Webhosting\Ftp\FtpUserId;
use ParkManager\Domain\Webhosting\Space\SpaceId;

final class FtpUserNotFound extends NotFoundException
{
    public static function withId(FtpUserId $id): self
    {
        return new self(\sprintf('FtpUser with id "%s" does not exist.', $id->toString()), ['{id}' => $id]);
    }

    public static function withUsername(string $username, SpaceId $space): self
    {
        return new self(
            \sprintf('FtpUser with username "%s" does not exist in space %s.', $username, $space),
            ['{username}' => $username, '{space}' => $space]
        );
    }
}
