<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\Webhosting\Tests\Fixtures\Model\Package\Command;

use Prooph\Common\Messaging\Command;
use Prooph\Common\Messaging\PayloadTrait;

final class CreatePackage extends Command
{
    use PayloadTrait;

    public function __construct()
    {
        $this->init();
    }
}
