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

use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\ConstantTime\Binary;

/**
 * A split-token value-object.
 *
 * Caution before working on this class understand that any change can
 * potentially introduce a security problem. Please consult a security
 * expert before accepting these changes as-is:
 *
 * * The selector and verifier are base64-uri-safe encoded using a constant-time
 *   encoder. Do not replace these with a regular encoder as this leaks timing
 *   information, making it possible to perform side-channel attacks.
 *
 * * The selector is used as ID to find the token, leaking this value
 *   has no negative effect. The index of the storage already leaks timing.
 *
 * * The verifier is used _as a password_ to authenticate the token,
 *   only the 'full token' has the original value. The storage holds
 *   a crypto hashed (Argon2i) version of the verifier.
 *
 * * When validating the token, the provided verifier is crypto
 *   compared in *constant-time* for equality.
 *
 * The 'full token' is to be shared with the receiver only!
 *
 * THE TOKEN HOLDS THE ORIGINAL "VERIFIER", DO NOT STORE THE TOKEN
 * IN A DATABASE UNLESS A PROPER FORM OF STORAGE ENCRYPTION IS USED!
 *
 * Example (for illustration):
 *
 * <code>
 * $userId = ...; // Can be null
 *
 * // Create
 * $token = SplitToken::generate($userId);
 *
 * // The $authToken is to be shared with the receiver (eg. the user) only.
 * // And is URI safe.
 * //
 * // DO NOT STORE "THIS" VALUE IN THE DATABASE! Store the selector and verifier-hash instead.
 * $authToken = $token->token();
 *
 * // UPDATE site_user
 * // SET
 * //   recovery_selector = $authToken->selector(),
 * //   recovery_verifier = $authToken->verifierHash(),
 * //   recovery_timestamp = NOW()
 * // WHERE user_id = ...
 *
 *
 * // Verification step:
 * $token = SplitToken::fromString($_GET['token']);
 *
 * // $result = SELECT user_id, recover_verifier WHERE recover_selector = $token->selector()
 *
 * $accepted = $token->verify($result['recover_verifier'], $result['user_id']);
 * <code>
 *
 * Note: Invoking verifierHash() doesn't work for a reconstructed SplitToken object.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class SplitToken
{
    private const SELECTOR_BYTES = 24;
    private const VERIFIER_BYTES = 18;
    private const TOKEN_CHAR_LENGTH = (self::SELECTOR_BYTES * 4 / 3) + (self::VERIFIER_BYTES * 4 / 3);

    private $selector;
    private $verifier;
    private $verifierHash;
    private $token;

    /**
     * Generate a new SplitToken instance.
     *
     * Caution: The token should not be stored! Store the selector and verifier-hash
     * separate.
     *
     * @param string $id Optional id to bind the token a specific entity (highly recommended)
     *
     * @return SplitToken
     */
    public static function generate(?string $id = null): self
    {
        $selector = Base64UrlSafe::encode(\random_bytes(static::SELECTOR_BYTES));
        $verifier = Base64UrlSafe::encode(\random_bytes(static::VERIFIER_BYTES));

        $instance = new self();
        $instance->selector = $selector;
        $instance->verifier = $verifier;
        $instance->token = $selector.$verifier;
        $instance->verifierHash = \Sodium\crypto_pwhash_str(
            $verifier.':'.($id ?? '\0'),
            \Sodium\CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            \Sodium\CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );

        return $instance;
    }

    /**
     * Returns the selector to store/find the token in storage.
     *
     * @return string A 24 byte string
     */
    public function selector(): string
    {
        return $this->selector;
    }

    /**
     * Returns the verifier-hash for storage.
     *
     * @return string An Argon2i crypt-hashed string
     */
    public function verifierHash(): string
    {
        if (null === $this->verifierHash) {
            throw new \RuntimeException('verifierHash() does not work for a reconstructed SplitToken object.');
        }

        return $this->verifierHash;
    }

    /**
     * Returns the token (selector + verifier) for authentication.
     *
     * @return string
     */
    public function token(): string
    {
        return $this->token;
    }

    /**
     * Reconstruct SplitToken from user provided string.
     *
     * @param string $token
     *
     * @return SplitToken
     */
    public static function fromString(string $token): self
    {
        if (Binary::safeStrlen($token) < self::TOKEN_CHAR_LENGTH) {
            // Don't zero memory as the value is invalid.
            throw new \RuntimeException('Invalid token provided.');
        }

        $instance = new self();
        $instance->token = $token;
        $instance->selector = Binary::safeSubstr($token, 0, 32);
        $instance->verifier = Binary::safeSubstr($token, 32);

         // Don't (re)generate as this needs the salt of the stored hash.
        $instance->verifierHash = null;

        return $instance;
    }

    /**
     * Verify this token against a (stored) selector and verifier-hash.
     *
     * This method is to be used once the SplitToken is reconstructed
     * from a user-provided string.
     *
     * @param string      $selector     The selector (as stored)
     * @param string      $verifierHash The verifier hash (as stored)
     * @param null|string $id           Id this token was bound to during generation
     *
     * @return bool
     */
    public function matches(string $selector, string $verifierHash, ?string $id = null): bool
    {
        // Ensure the algorithm works as expected, and this API is used correctly.
        if ($selector !== $this->selector) {
            return false;
        }

        if (\Sodium\crypto_pwhash_str_verify($verifierHash, $this->verifier.':'.($id ?? '\0'))) {
            return true;
        }

        return false;
    }

    /**
     * Produce a new SplitTokenValue instance (with the selector and verifierHash).
     *
     * Note: This method doesn't work when reconstructed from a string.
     *
     * @param \DateTimeImmutable|null $expiresAt
     * @param array|null              $metadata
     *
     * @return SplitTokenValueHolder
     */
    public function toValueHolder(?\DateTimeImmutable $expiresAt = null, array $metadata = []): SplitTokenValueHolder
    {
        return new SplitTokenValueHolder($this->selector(), $this->verifierHash(), $expiresAt, $metadata);
    }

    /**
     * Returns the token as base64 URI-safe encoded string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->token();
    }

    /**
     * Wipe it from memory after it's been used.
     */
    public function __destruct()
    {
        \Sodium\memzero($this->token);
        \Sodium\memzero($this->verifier);

        if (null !== $this->verifierHash) {
            \Sodium\memzero($this->verifierHash);
        }
    }
}
