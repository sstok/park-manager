<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\User;

use ParkManager\Domain\User\UserId;
use SplFileInfo;
use Symfony\Component\Validator\Constraints\Image;

final class ChangeAvatar
{
    public function __construct(
        public UserId $id,

        #[Image(maxSize: '60kib', mimeTypes: ['image/jpeg'], maxWidth: 460, maxHeight: 460, detectCorrupted: true)]
        public SplFileInfo $tempFile
    ) {
    }
}
