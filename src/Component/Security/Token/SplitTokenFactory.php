<?php

declare(strict_types=1);

/*
 * This file is part of the Park-Manager project.
 *
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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
     *     $id
     * );
     * ```
     *
     * @param null|string $id
     *
     * @return SplitToken
     *
     * @see \ParagonIE\Halite\HiddenString
     */
    public function generate(?string $id = null): SplitToken;

    /**
     * Recreates a SplitToken object from a HiddenString (provided by eg. a user).
     *
     * Example:
     *
     * ```
     * return SplitToken::fromString($token);
     * ```
     *
     * @param string $token
     *
     * @return SplitToken
     */
    public function fromString(string $token): SplitToken;
}
