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
 * The SodiumSplitTokenFactory uses Libsodium Argon2id for hashing
 * the SplitToken verifier.
 */
final class SodiumSplitTokenFactory implements SplitTokenFactory
{
    public function generate(?string $id = null, ?\DateTimeImmutable $expirationTimestamp = null): SplitToken
    {
        return SplitToken::create(
            // DO NOT ENCODE HERE (always provide as raw binary)!
            new HiddenString(\random_bytes(SplitToken::TOKEN_CHAR_LENGTH), false, true),
            [$this, 'hasher'],
            [$this, 'validator'],
            $id,
            $expirationTimestamp
        );
    }

    public function fromString(string $token): SplitToken
    {
        return SplitToken::fromString($token, [$this, 'hasher'], [$this, 'validator']);
    }

    /** @internal */
    public function hasher(string $input): string
    {
        return \sodium_crypto_pwhash_str(
            $input,
            \SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            \SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
    }

    /** @internal */
    public function validator(string $verifierHash, string $verifiier): bool
    {
        return \sodium_crypto_pwhash_str_verify($verifierHash, $verifiier);
    }
}
