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

final class Argon2SplitToken extends SplitToken
{
    protected function configureHasher(array $config = [])
    {
        $this->config = array_merge(
            [
                'memory_cost' => \PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
                'time_cost' => \PASSWORD_ARGON2_DEFAULT_TIME_COST,
                'threads' => \PASSWORD_ARGON2_DEFAULT_THREADS,
            ],
            $config
        );
    }

    protected function verifyHash(string $hash, string $verifier): bool
    {
        return \password_verify($verifier, $hash);
    }

    protected function hashVerifier(string $verifier): string
    {
        return \password_hash($verifier, \PASSWORD_ARGON2I, $this->config);
    }
}
