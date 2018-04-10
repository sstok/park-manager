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

interface SplitTokenFactory
{
    /**
     * Generates a new SplitToken object.
     *
     * Example:
     *
     * ```
     * return SplitToken::create(
     *     new HiddenString(\random_bytes(SplitToken::TOKEN_CHAR_LENGTH), false, true), // DO NOT ENCODE HERE (always provide as raw binary)!
     *     [$this, 'hasher'], // ($hash, $verifier) -- sodium_crypto_pwhash_str($hash, $verifier)
     *     [$this, 'validator'], // ($value) -- sodium_crypto_pwhash_str_verify($value)
     *     $id,
     *     $expirationTimestamp
     * );
     * ```
     *
     * @param null|string             $id
     * @param \DateTimeImmutable|null $expiresAt
     *
     * @return SplitToken
     *
     * @see \ParagonIE\Halite\HiddenString
     */
    public function generate(?string $id = null, ?\DateTimeImmutable $expiresAt = null): SplitToken;

    /**
     * Recreates a SplitToken object from a HiddenString (provided by eg. a user).
     *
     * Example:
     *
     * ```
     * return SplitToken::fromString(
     *     $token,
     *     [$this, 'hasher'],
     *     [$this, 'validator']
     * );
     * ```
     *
     * @param string $token
     *
     * @return SplitToken
     */
    public function fromString(string $token): SplitToken;
}
