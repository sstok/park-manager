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

namespace ParkManager\Component\User\Security;

use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * ArgonPasswordEncoder uses the Argon2i hashing algorithm provided by libsodium.
 *
 * To be replaced once Symfony Security Component adds support.
 *
 * @author Zander Baldwin
 *
 * @codeCoverageIgnore
 */
class Argon2iPasswordEncoder extends BasePasswordEncoder
{
    /**
     * {@inheritdoc}
     */
    public function encodePassword($raw, $salt)
    {
        if ($this->isPasswordTooLong($raw)) {
            throw new BadCredentialsException('Invalid password.');
        }

        if (function_exists('sodium_crypto_pwhash_str')) {
            return $this->encodePasswordNative($raw);
        }
        if (extension_loaded('libsodium')) {
            return $this->encodePasswordExtension($raw);
        }

        throw new \LogicException(
            'Argon2i algorithm is not supported. Please install libsodium extension or upgrade to PHP 7.2+.'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordValid($encoded, $raw, $salt)
    {
        $valid = false;

        if (function_exists('sodium_crypto_pwhash_str_verify')) {
            $valid = !$this->isPasswordTooLong($raw) && sodium_crypto_pwhash_str_verify($encoded, $raw);
            \sodium_memzero($raw);
        } elseif (extension_loaded('libsodium')) {
            $valid = !$this->isPasswordTooLong($raw) && \Sodium\crypto_pwhash_str_verify($encoded, $raw);
            \Sodium\memzero($raw);
        }

        return $valid;
    }

    private function encodePasswordNative($raw)
    {
        $hash = \sodium_crypto_pwhash_str(
            $raw,
            \SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            \SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
        \sodium_memzero($raw);

        return $hash;
    }

    private function encodePasswordExtension($raw)
    {
        $hash = \Sodium\crypto_pwhash_str(
            $raw,
            \Sodium\CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            \Sodium\CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
        \Sodium\memzero($raw);

        return $hash;
    }
}
