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

use ParagonIE\Halite\HiddenString;
use ParkManager\Component\Security\Token\SplitToken;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SplitTokenTest extends TestCase
{
    private const FULL_TOKEN = '1zUeXUvr4LKymANBB_bLEqiP5GPr-Pha_OR6OOnV1o8Vy_rWhDoxKNIt';
    private const SELECTOR = '1zUeXUvr4LKymANBB_bLEqiP5GPr-Pha';

    private static $randValue;

    /**
     * @beforeClass
     */
    public static function createRandomBytes()
    {
        self::$randValue = new HiddenString(hex2bin('d7351e5d4bebe0b2b298034107f6cb12a88fe463ebf8f85afce47a38e9d5d68f15cbfad6843a3128d22d'), false, true);
    }

    /**
     * @test
     */
    public function it_validates_the_correct_length()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid token-data provided, expected exactly 42 bytes.');

        SplitToken::create(
            $fullToken = new HiddenString('NanananaBatNan', false, true),
            function (string $value) { },
            function () { }
        );
    }

    /**
     * @test
     */
    public function it_creates_a_split_token_without_id()
    {
        $splitToken = SplitToken::create(
            self::$randValue,
            function (string $value) {
                return $value;
            },
            function () {
                return false;
            }
        );

        self::assertEquals(self::FULL_TOKEN, $token = $splitToken->token()->getString());
        self::assertEquals(self::SELECTOR, $selector = $splitToken->selector());
    }

    /**
     * @test
     */
    public function it_creates_a_split_token_with_id()
    {
        $splitToken = SplitToken::create(
            $fullToken = self::$randValue,
            function (string $value) {
                return $value;
            },
            function () {
                return false;
            },
            '50'
        );

        self::assertEquals(self::FULL_TOKEN, $token = $splitToken->token()->getString());
        self::assertEquals(self::SELECTOR, $selector = $splitToken->selector());
    }

    /**
     * @test
     */
    public function it_produces_a_SplitTokenValueHolder()
    {
        $verifier = '';
        $splitToken = SplitToken::create(
            self::$randValue,
            function (string $value) use (&$verifier) {
                $verifier = $value;

                return $value;
            },
            function () {
                return false;
            }
        );

        $value = $splitToken->toValueHolder();

        self::assertEquals($splitToken->selector(), $value->selector());
        self::assertEquals($verifier, $value->verifierHash());
        self::assertEquals([], $value->metadata());
        self::assertFalse($value->isExpired());
        self::assertFalse($value->isExpired(new \DateTimeImmutable('-5 minutes')));
    }

    /**
     * @test
     */
    public function it_produces_a_SplitTokenValueHolder_with_metadata()
    {
        $verifier = '';
        $splitToken = SplitToken::create(
            $fullToken = self::$randValue,
            function (string $value) use (&$verifier) {
                $verifier = $value;

                return $value;
            },
            function () {
                return false;
            }
        );

        $value = $splitToken->toValueHolder(['he' => 'now']);

        self::assertEquals($verifier, $value->verifierHash());
        self::assertEquals(['he' => 'now'], $value->metadata());
    }

    /**
     * @test
     */
    public function it_produces_a_SplitTokenValueHolder_with_expiration()
    {
        $date = new \DateTimeImmutable('+5 minutes');
        $splitToken = SplitToken::create(
            $fullToken = self::$randValue,
            function (string $value) use (&$verifier) {
                $verifier = $value;

                return $value;
            },
            function () {
                return false;
            },
            null,
            $date
        );

        $value = $splitToken->toValueHolder();

        self::assertTrue($value->isExpired($date->modify('+10 minutes')));
        self::assertFalse($value->isExpired($date));
        self::assertEquals([], $value->metadata());
    }

    /**
     * @test
     */
    public function it_reconstructs_from_string()
    {
        $splitTokenReconstituted = SplitToken::fromString(
            self::FULL_TOKEN,
            function (string $value) use (&$verifier) {
                $verifier = $value;

                return $value;
            },
            function () {
                return false;
            }
        );

        self::assertEquals(self::FULL_TOKEN, $splitTokenReconstituted->token()->getString());
        self::assertEquals(self::SELECTOR, $splitTokenReconstituted->selector());
    }

    /**
     * @test
     */
    public function it_fails_when_creating_holder_with_string_constructed()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('toValueHolder() does not work SplitToken object created with fromString().');

        SplitToken::fromString(
            self::FULL_TOKEN,
            function (string $value) use (&$verifier) {
                $verifier = $value;

                return $value;
            },
            function () {
                return false;
            }
        )->toValueHolder();
    }

    /**
     * @test
     */
    public function it_verifies_SplitToken_from_string_and_no_id()
    {
        $hasher = function (string $value) {
            return $value;
        };
        $verifierCallable = function (string $val1, string $val2) {
            return $val1 === $val2;
        };

        // Stored.
        $splitTokenHolder = SplitToken::create(self::$randValue, $hasher, $verifierCallable)->toValueHolder();

        // Reconstructed.
        $fromString = SplitToken::fromString(self::FULL_TOKEN, $hasher, $verifierCallable);

        self::assertTrue($fromString->matches($splitTokenHolder));
        self::assertFalse($fromString->matches($splitTokenHolder, '50'));
    }

    /**
     * @test
     */
    public function it_verifies_SplitToken_from_string_SplitToken_and_id()
    {
        $hasher = function (string $value) {
            return $value;
        };
        $verifierCallable = function (string $val1, string $val2) {
            return $val1 === $val2;
        };

        // Stored.
        $splitTokenHolder = SplitToken::create(self::$randValue, $hasher, $verifierCallable, '50')->toValueHolder();

        // Reconstructed.
        $fromString = SplitToken::fromString(self::FULL_TOKEN, $hasher, $verifierCallable);

        self::assertTrue($fromString->matches($splitTokenHolder, '50'));
        self::assertFalse($fromString->matches($splitTokenHolder, '60'));
        self::assertFalse($fromString->matches($splitTokenHolder));
    }

    /**
     * @test
     */
    public function it_verifies_SplitToken_from_string_selector()
    {
        $hasher = function (string $value) {
            return $value;
        };
        $verifierCallable = function (string $val1, string $val2) {
            return $val1 === $val2;
        };

        // Stored.
        $splitTokenHolder = SplitToken::create(self::$randValue, $hasher, $verifierCallable, '50')->toValueHolder();

        // Reconstructed.
        $fromString = SplitToken::fromString('12UeXUvr4LKymANBB_bLEqiP5GPr-Pha_OR6OOnV1o8Vy_rWhDoxKNIt', $hasher, $verifierCallable);

        self::assertFalse($fromString->matches($splitTokenHolder));
        self::assertFalse($fromString->matches($splitTokenHolder, '50'));
    }

    /**
     * @test
     */
    public function it_verifies_SplitToken_from_string_with_expiration()
    {
        $hasher = function (string $value) {
            return $value;
        };
        $verifierCallable = function (string $val1, string $val2) {
            return $val1 === $val2;
        };

        // Stored.
        $splitTokenHolder = SplitToken::create(
            self::$randValue,
            $hasher,
            $verifierCallable,
            null,
            new \DateTimeImmutable('-5 minutes')
        )->toValueHolder();

        // Reconstructed.
        $fromString = SplitToken::fromString(self::FULL_TOKEN, $hasher, $verifierCallable);

        self::assertFalse($fromString->matches($splitTokenHolder));
        self::assertFalse($fromString->matches($splitTokenHolder, '50'));
    }
}
