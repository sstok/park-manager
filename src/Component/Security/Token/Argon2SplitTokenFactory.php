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

namespace ParkManager\Component\Security\Token;

use ParagonIE\Halite\HiddenString;

/**
 * Uses Libsodium Argon2i(d) for hashing the SplitToken verifier.
 */
final class Argon2SplitTokenFactory implements SplitTokenFactory
{
    private $config;
    private $defaultExpirationTimestamp;

    public function __construct(array $config = [], ?\DateTimeImmutable $defaultExpirationTimestamp = null)
    {
        $this->config = $config;
        $this->defaultExpirationTimestamp = $defaultExpirationTimestamp;
    }

    public function generate(?string $id = null, ?\DateTimeImmutable $expirationTimestamp = null): SplitToken
    {
        $splitToken = Argon2SplitToken::create(
            // DO NOT ENCODE HERE (always provide as raw binary)!
            new HiddenString(\random_bytes(SplitToken::TOKEN_CHAR_LENGTH), false, true),
            $id,
            $this->config
        );

        if (null !== $this->defaultExpirationTimestamp) {
            $splitToken->expireAt($this->defaultExpirationTimestamp);
        }

        return $splitToken;
    }

    public function fromString(string $token): SplitToken
    {
        return Argon2SplitToken::fromString($token);
    }
}
