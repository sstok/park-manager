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
 * The FakeSplitTokenFactory always uses the same non-random value
 * for the SplitToken to speed-up tests.
 *
 * !! THIS IMPLEMENTATION IS NOT SECURE, USE ONLY FOR TESTING !!
 */
final class FakeSplitTokenFactory implements SplitTokenFactory
{
    public const FULL_TOKEN = '1zUeXUvr4LKymANBB_bLEqiP5GPr-Pha_OR6OOnV1o8Vy_rWhDoxKNIt';

    private $randomValue;

    public static function instance(?string $randomValue = null)
    {
        return new self($randomValue);
    }

    public function __construct(?string $randomValue = null)
    {
        $this->randomValue = $randomValue ?? hex2bin('d7351e5d4bebe0b2b298034107f6cb12a88fe463ebf8f85afce47a38e9d5d68f15cbfad6843a3128d22d');
    }

    public function generate(?string $id = null, ?\DateTimeImmutable $expirationTimestamp = null): SplitToken
    {
        return SplitToken::create(
            new HiddenString($this->randomValue, false, true),
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
    public function hasher(string $input)
    {
        return $input;
    }

    /** @internal */
    public function validator(string $input1, string $input2)
    {
        return $input1 === $input2;
    }
}
