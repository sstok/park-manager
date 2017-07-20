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

namespace ParkManager\Component\User\Tests\Security;

use ParkManager\Component\User\Security\Argon2iPasswordEncoder;
use PHPUnit\Framework\TestCase;

/**
 * @author Elnur Abdurrakhimov <elnur@elnur.pro>
 *
 * @group slow
 *
 * @internal
 */
final class Argon2iPasswordEncoderTest extends TestCase
{
    public const PASSWORD = 'password';

    public function setUp()
    {
        if (!function_exists('sodium_crypto_pwhash_str') && !extension_loaded('libsodium')) {
            $this->markTestSkipped('Libsodium is not available.');
        }
    }

    public function testValidation()
    {
        $encoder = new Argon2iPasswordEncoder();
        $result = $encoder->encodePassword(self::PASSWORD, 'salt');
        $this->assertTrue($encoder->isPasswordValid($result, self::PASSWORD, 'salt'));
        $this->assertFalse($encoder->isPasswordValid($result, 'anotherPassword', 'salt'));
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\BadCredentialsException
     */
    public function testEncodePasswordLength()
    {
        $encoder = new Argon2iPasswordEncoder();
        $encoder->encodePassword(str_repeat('a', 4097), 'salt');
    }

    public function testCheckPasswordLength()
    {
        $encoder = new Argon2iPasswordEncoder();
        $result = $encoder->encodePassword(str_repeat('a', 4096), 'salt');
        $this->assertFalse($encoder->isPasswordValid($result, str_repeat('a', 4097), 'salt'));
        $this->assertTrue($encoder->isPasswordValid($result, str_repeat('a', 4096), 'salt'));
    }

    public function testUserProvidedSaltIsNotUsed()
    {
        $encoder = new Argon2iPasswordEncoder();
        $result = $encoder->encodePassword(self::PASSWORD, 'salt');
        $this->assertTrue($encoder->isPasswordValid($result, self::PASSWORD, 'anotherSalt'));
    }
}
