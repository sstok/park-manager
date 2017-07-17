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

namespace ParkManager\Component\User\Model\Command;

use Prooph\Common\Messaging\Command;
use Prooph\Common\Messaging\PayloadTrait;

/**
 * ChangeUserPassword (with encoded-password).
 *
 * Note: For security reasons the password is provided in encoded format,
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class ChangeUserPassword extends Command
{
    use PayloadTrait;
    use UserIdTrait;

    private $password;

    public function __construct(string $id, ?string $password)
    {
        $this->password = $password;

        $this->init();
        $this->setPayload(['id' => $id, 'password' => $password]);
    }

    public function password(): ?string
    {
        return $this->payload['password'];
    }
}
