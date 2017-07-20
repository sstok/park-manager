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
use ParkManager\Component\Security\Token\SplitTokenValueHolder;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @group slow
 */
final class SplitTokenValueHolderTest extends TestCase
{
    public const VERIFIER = '$argon2i$v=19$m=32768,t=4,p=1$LU0TzgT8jcnYpqRGzPFVbA$ik1q480aF9RGRxP2hxyVjAyDBkuE/SyO7+joMzpiqEY';
    public const SELECTOR = 'ZcqcqWqW-YAhgmLobo6tps0Xiyq678Di';
    public const TOKEN = 'ZcqcqWqW-YAhgmLobo6tps0Xiyq678DiLgRZaJvbTI2QxGEzD2a7swBs';

    /** @test */
    public function it_constructable()
    {
        $value = new SplitTokenValueHolder(self::SELECTOR, self::VERIFIER);

        self::assertEquals(self::SELECTOR, $value->selector());
        self::assertEquals(self::VERIFIER, $value->verifierHash());
        self::assertEquals([], $value->metadata());
        self::assertFalse($value->isExpired());
        self::assertNull($value->expiresAt());
    }

    /** @test */
    public function it_constructable_with_expiration()
    {
        $value = new SplitTokenValueHolder(self::SELECTOR, self::VERIFIER, $date = new \DateTimeImmutable('+5 minutes'));

        self::assertEquals(self::SELECTOR, $value->selector());
        self::assertEquals(self::VERIFIER, $value->verifierHash());
        self::assertTrue($value->isExpired($date->modify('+10 minutes')));
        self::assertFalse($value->isExpired($date->modify('-1 minute')));
        self::assertEquals($date, $value->expiresAt());
        self::assertEquals([], $value->metadata());
    }

    /** @test */
    public function its_expiration_is_compared_by_timestamp()
    {
        $date = new \DateTimeImmutable('+5 minutes', new \DateTimeZone('Europe/Amsterdam')); // 1 hour difference
        $date2 = new \DateTimeImmutable('+5 minutes', new \DateTimeZone('UTC'));

        $value = new SplitTokenValueHolder(self::SELECTOR, self::VERIFIER, $date);

        self::assertTrue($value->isExpired($date->modify('+10 minutes')));
        self::assertTrue($value->isExpired($date2->modify('+10 minutes')));
        self::assertFalse($value->isExpired($date));
        self::assertFalse($value->isExpired($date));
    }

    /** @test */
    public function it_constructable_with_metadata()
    {
        $value = new SplitTokenValueHolder(self::SELECTOR, self::VERIFIER, null, ['foo' => 'bar']);

        self::assertEquals(self::SELECTOR, $value->selector());
        self::assertEquals(self::VERIFIER, $value->verifierHash());
        self::assertEquals(['foo' => 'bar'], $value->metadata());
        self::assertFalse($value->isExpired());
    }

    /** @test */
    public function it_produces_a_new_instance_when_changing_metadata()
    {
        $value = new SplitTokenValueHolder(self::SELECTOR, self::VERIFIER, $date = new \DateTimeImmutable('+5 minutes'), ['foo' => 'bar']);

        $value2 = $value->withMetadata(['name' => 'doctor']);

        self::assertNotSame($value, $value2);
        self::assertEquals(self::SELECTOR, $value2->selector());
        self::assertEquals(self::VERIFIER, $value2->verifierHash());
        self::assertEquals(['name' => 'doctor'], $value2->metadata());
        self::assertTrue($value->isExpired($date->modify('+10 minutes')));
        self::assertFalse($value->isExpired($date));
    }

    /**
     * @test
     * @slowThreshold 1000
     */
    public function it_verifies_with_no_id()
    {
        $token = SplitToken::fromString(self::TOKEN);
        $value = new SplitTokenValueHolder(self::SELECTOR, self::VERIFIER);

        self::assertTrue($value->isValid($token));
        self::assertFalse($value->isValid($token, '50'));
    }

    /**
     * @test
     * @slowThreshold 1000
     */
    public function it_verifies_generated_SplitToken_and_id()
    {
        $token = SplitToken::fromString('S1th74ywhDETYAaXWi-2Bee2_ltx-JPGKs9SVvbZCkMi8ZxiEVMBw68S');
        $verifierHash = '$argon2i$v=19$m=32768,t=4,p=1$8nUJsz8Me7341Ryx73sc7g$lXA1SjnLCx87qYXOHl94b1iccECXVzcmHEIGuyLWSc4';
        $selector = 'S1th74ywhDETYAaXWi-2Bee2_ltx-JPG';

        $value = new SplitTokenValueHolder($selector, $verifierHash);

        self::assertTrue($value->isValid($token, '50'));
        self::assertFalse($value->isValid($token, '60'));
        self::assertFalse($value->isValid($token));
    }

    /**
     * @test
     * @slowThreshold 3000
     */
    public function it_verifies_with_expiration()
    {
        $token = SplitToken::fromString(self::TOKEN);
        $value = new SplitTokenValueHolder(self::SELECTOR, self::VERIFIER, $date = new \DateTimeImmutable('+1 second'));

        self::assertTrue($value->isValid($token));

        sleep(2);
        self::assertFalse($value->isValid($token));
    }
}
