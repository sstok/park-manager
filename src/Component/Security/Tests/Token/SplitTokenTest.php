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

namespace ParkManager\Component\Security\Tests\Token;

use ParkManager\Component\Security\Token\SplitToken;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @group slow
 */
final class SplitTokenTest extends TestCase
{
    /**
     * @test
     * @slowThreshold 1000
     */
    public function it_generates_a_split_token_without_id()
    {
        $splitToken = SplitToken::generate();

        self::assertNotNull($token = $splitToken->token());
        self::assertNotEquals($token, $selector = $splitToken->selector());
        self::assertEquals($token, (string) $splitToken);
        self::assertNotNull($verifier = $splitToken->verifierHash());
        self::assertStringStartsWith($selector, $token);
        self::assertStringEndsNotWith($selector, $verifier);
        self::assertStringStartsWith('$argon2i$', $verifier);
    }

    /**
     * @test
     * @slowThreshold 1000
     */
    public function it_generates_a_split_token_with_id()
    {
        $splitToken = SplitToken::generate('50');

        self::assertNotNull($token = $splitToken->token());
        self::assertNotEquals($token, $selector = $splitToken->selector());
        self::assertNotNull($verifier = $splitToken->verifierHash());
        self::assertStringStartsWith($selector, $token);
        self::assertStringEndsNotWith($selector, $verifier);
        self::assertStringStartsWith('$argon2i$', $verifier);
    }

    /**
     * @test
     * @slowThreshold 1000
     */
    public function it_can_produce_a_SplitTokenValueHolder()
    {
        $splitToken = SplitToken::generate();
        $value = $splitToken->toValueHolder();

        self::assertEquals($splitToken->selector(), $value->selector());
        self::assertEquals($splitToken->verifierHash(), $value->verifierHash());
        self::assertEquals([], $value->metadata());
        self::assertFalse($value->isExpired());
    }

    /**
     * @test
     * @slowThreshold 1000
     */
    public function it_can_produce_a_SplitTokenValueHolder_with_expiration()
    {
        $splitToken = SplitToken::generate();
        $value = $splitToken->toValueHolder($date = new \DateTimeImmutable('+5 minutes'));

        self::assertEquals($value->selector(), $splitToken->selector());
        self::assertEquals($value->verifierHash(), $splitToken->verifierHash());
        self::assertTrue($value->isExpired($date->modify('+10 minutes')));
        self::assertFalse($value->isExpired($date));
        self::assertEquals([], $value->metadata());
    }

    /**
     * @test
     * @slowThreshold 1000
     */
    public function it_can_produce_a_SplitTokenValueHolder_with_metadata()
    {
        $splitToken = SplitToken::generate();
        $value = $splitToken->toValueHolder(null, ['foo' => 'bar']);

        self::assertEquals($value->selector(), $splitToken->selector());
        self::assertEquals($value->verifierHash(), $splitToken->verifierHash());
        self::assertEquals(['foo' => 'bar'], $value->metadata());
        self::assertFalse($value->isExpired());
    }

    /**
     * @test
     * @slowThreshold 1000
     */
    public function its_reconstruction_validates_minimum_length()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid token provided.');

        SplitToken::fromString($token = 'ngyG');
    }

    /**
     * @test
     * @slowThreshold 1000
     */
    public function it_reconstructs_from_string()
    {
        $splitTokenReconstituted = SplitToken::fromString($token = 'ngyG-7F0202Jl8yP-GiZvRQlzizrCDeprqTOY7QjYPplwRmLEcgGkPty');

        self::assertEquals($token, $splitTokenReconstituted->token());
        self::assertNotNull('ngyG-7F0202Jl8yP-GiZvRQlzizrCDep', $splitTokenReconstituted->selector());
        self::assertStringEndsNotWith('rqTOY7QjYPplwRmLEcgGkPty', $splitTokenReconstituted->selector());
        self::assertStringEndsNotWith('RmLEcgGkPty', $splitTokenReconstituted->selector());
    }

    /**
     * @test
     * @slowThreshold 1000
     */
    public function it_fails_when_verifierHash_is_invoked_on_reconstructed()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('verifierHash() does not work for a reconstructed SplitToken object.');

        SplitToken::fromString($token = 'ngyG-7F0202Jl8yP-GiZvRQlzizrCDeprqTOY7QjYPplwRmLEcgGkPty')->verifierHash();
    }

    /**
     * @test
     * @slowThreshold 1000
     */
    public function it_verifies_generated_SplitToken_and_no_id()
    {
        $splitToken = SplitToken::generate();

        self::assertTrue($splitToken->matches($splitToken->selector(), $splitToken->verifierHash()));
        self::assertFalse($splitToken->matches($splitToken->selector(), $splitToken->verifierHash(), '50'));
        self::assertFalse($splitToken->matches($splitToken->verifierHash(), $splitToken->selector()));
    }

    /**
     * @test
     * @slowThreshold 1000
     */
    public function it_verifies_generated_SplitToken_and_id()
    {
        $splitToken = SplitToken::generate('50');

        self::assertTrue($splitToken->matches($splitToken->selector(), $splitToken->verifierHash(), '50'));
        self::assertFalse($splitToken->matches($splitToken->selector(), $splitToken->verifierHash(), '60'));
        self::assertFalse($splitToken->matches($splitToken->selector(), $splitToken->verifierHash()));
        self::assertFalse($splitToken->matches($splitToken->verifierHash(), $splitToken->selector()));
    }

    /**
     * @test
     * @slowThreshold 1000
     */
    public function it_verifies_reconstructed()
    {
        $token = 'ZcqcqWqW-YAhgmLobo6tps0Xiyq678DiLgRZaJvbTI2QxGEzD2a7swBs';
        $verifierHash = '$argon2i$v=19$m=32768,t=4,p=1$LU0TzgT8jcnYpqRGzPFVbA$ik1q480aF9RGRxP2hxyVjAyDBkuE/SyO7+joMzpiqEY';
        $selector = 'ZcqcqWqW-YAhgmLobo6tps0Xiyq678Di';

        $splitToken = SplitToken::fromString($token);

        self::assertTrue($splitToken->matches($selector, $verifierHash));
        self::assertFalse($splitToken->matches($selector, $verifierHash, '50'));
        self::assertFalse($splitToken->matches($verifierHash, $selector));
        self::assertFalse($splitToken->matches($selector, 'foo'));
    }

    /**
     * @test
     * @slowThreshold 1000
     */
    public function it_verifies_reconstructed_with_id()
    {
        $token = 'S1th74ywhDETYAaXWi-2Bee2_ltx-JPGKs9SVvbZCkMi8ZxiEVMBw68S';
        $verifierHash = '$argon2i$v=19$m=32768,t=4,p=1$8nUJsz8Me7341Ryx73sc7g$lXA1SjnLCx87qYXOHl94b1iccECXVzcmHEIGuyLWSc4';
        $selector = 'S1th74ywhDETYAaXWi-2Bee2_ltx-JPG';

        $splitToken = SplitToken::fromString($token);

        self::assertTrue($splitToken->matches($selector, $verifierHash, '50'));
        self::assertFalse($splitToken->matches($selector, $verifierHash, '60'));
        self::assertFalse($splitToken->matches($selector, $verifierHash));
        self::assertFalse($splitToken->matches($verifierHash, $selector));
        self::assertFalse($splitToken->matches($selector, 'foo', '50'));
        self::assertFalse($splitToken->matches($selector, 'foo'));
    }
}
