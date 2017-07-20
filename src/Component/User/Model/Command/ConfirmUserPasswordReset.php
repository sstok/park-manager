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

use ParkManager\Component\Security\Token\SplitToken;
use Prooph\Common\Messaging\Command;
use Prooph\Common\Messaging\PayloadTrait;

/**
 * ConfirmUserPasswordReset (with encoded-password).
 *
 * Note: For security reasons the password is provided in encoded format.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class ConfirmUserPasswordReset extends Command
{
    use PayloadTrait;

    private $token;

    public function __construct(SplitToken $token, string $password)
    {
        $this->token = $token;

        $this->init();
        $this->setPayload(['token' => $token->token(), 'password' => $password]);
    }

    public function token(): SplitToken
    {
        if (null === $this->token) {
            $this->token = SplitToken::fromString($this->payload['token']);
        }

        return $this->token;
    }

    public function password(): string
    {
        return $this->payload['password'];
    }
}
