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
use ParagonIE\Halite\HiddenString;

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
 * * The selector is used as ID to identify the token, leaking this value
 *   has no negative effect. The index of the storage already leaks timing.
 *
 * * The verifier is used _as a password_ to authenticate the token,
 *   only the 'full token' has the original value. The storage holds
 *   a crypto hashed version of the verifier.
 *
 * * When validating the token, the provided verifier is crypto
 *   compared in *constant-time* for equality.
 *
 * The 'full token' is to be shared with the receiver only!
 *
 * THE TOKEN HOLDS THE ORIGINAL "VERIFIER", DO NOT STORE THE TOKEN
 * IN A DIRECTLY UNLESS A PROPER FORM OF ENCRYPTION IS USED!
 *
 * Example (for illustration):
 *
 * <code>
 * $userId = ...; // Can be null
 *
 * // Create
 * $splitTokenFactory = ...;
 *
 * $token = $splitTokenFactory->create($userId);
 *
 * // The $authToken is to be shared with the receiver (eg. the user) only.
 * // And is URI safe.
 * //
 * // DO NOT STORE "THIS" VALUE IN THE DATABASE! Store the selector and verifier-hash instead.
 * $authToken = $token->token(); // HiddenString
 *
 * $holder = $token->toValueHolder();
 *
 * // UPDATE site_user
 * // SET
 * //   recovery_selector = $holder->selector(),
 * //   recovery_verifier = $holder->verifierHash(),
 * //   recovery_expires_at = $holder->expiresAt(),
 * //   recovery_metadata = $holder->metadata(),
 * //   recovery_timestamp = NOW()
 * // WHERE user_id = ...
 *
 *
 * // Verification step:
 * $token = $splitTokenFactory->fromString($_GET['token']);
 *
 * // $result = SELECT user_id, recover_verifier, recovery_expires_at, recovery_metadata WHERE recover_selector = $token->selector()
 * $holder = new SplitTokenValueHolder($token->selector(), $result['recovery_verifier'], $result['recovery_expires_at'], $result['recovery_metadata']);
 *
 * $accepted = $token->matches($holder, $result['user_id']);
 * <code>
 *
 * Note: Invoking toValueHolder() doesn't work for a reconstructed SplitToken object.
 */
final class SplitToken
{
    private const SELECTOR_BYTES = 24;
    private const VERIFIER_BYTES = 18;

    public const TOKEN_DATA_LENGTH = (self::VERIFIER_BYTES + self::SELECTOR_BYTES);
    public const TOKEN_CHAR_LENGTH = (self::SELECTOR_BYTES * 4 / 3) + (self::VERIFIER_BYTES * 4 / 3);

    private $selector;
    private $verifier;
    private $verifierHash;
    private $token;
    private $expiresAt;

    /**
     * @var callable
     */
    private $verifierHasher;

    /**
     * @var callable
     */
    private $verifierValidator;

    /**
     * Wipe it from memory after it's been used.
     */
    public function __destruct()
    {
        \sodium_memzero($this->verifier);

        if (null !== $this->verifierHash) {
            \sodium_memzero($this->verifierHash);
        }
    }

    /**
     * Creates a new SplitToken object based of the $token.
     *
     * The $randomBytes argument must provide a crypto-random string (wrapped in
     * a HiddenString object) of exactly {@see SplitToken::TOKEN_DATA_LENGTH} bytes.
     *
     * @param HiddenString            $randomBytes
     * @param callable                $verifierHasher    Callable to produce a hash of the verifier
     *                                                   string
     * @param callable                $verifierValidator Callable to validate the hash against
     *                                                   a user provided verifier value
     * @param null|string             $id                Optional id to bind the token a specific entity
     *                                                   (highly recommended)
     * @param \DateTimeImmutable|null $expiresAt
     *
     * @return SplitToken
     */
    public static function create(
        HiddenString $randomBytes,
        callable $verifierHasher,
        callable $verifierValidator,
        ?string $id = null,
        ?\DateTimeImmutable $expiresAt = null
    ): self {
        $bytesString = $randomBytes->getString();

        if (($i = Binary::safeStrlen($bytesString)) < self::TOKEN_DATA_LENGTH) {
            // Don't zero memory as the value is invalid.
            throw new \RuntimeException(sprintf('Invalid token-data provided, expected exactly %s bytes.', self::TOKEN_DATA_LENGTH));
        }

        $instance = new self();
        $instance->expiresAt = $expiresAt;
        $instance->selector = Base64UrlSafe::encode(Binary::safeSubstr($bytesString, 0, self::SELECTOR_BYTES));
        $instance->verifier = Base64UrlSafe::encode(Binary::safeSubstr($bytesString, self::SELECTOR_BYTES, self::VERIFIER_BYTES));
        $instance->token = new HiddenString($instance->selector.$instance->verifier, false, true);
        $instance->verifierHash = $verifierHasher($instance->verifier.':'.($id ?? '\0'));
        $instance->verifierValidator = $verifierValidator;
        $instance->verifierHasher = $verifierHasher;

        \sodium_memzero($bytesString);

        return $instance;
    }

    /**
     * Recreates a SplitToken object from a string.
     *
     * Note: The $token is zeroed from memory when valid.
     *
     * @param string   $token
     * @param callable $verifierHasher    Callable to produce a hash of the verifier
     *                                    string
     * @param callable $verifierValidator Callable to validate the hash against
     *                                    a user provided verifier value
     *
     * @return SplitToken
     */
    public static function fromString(string $token, callable $verifierHasher, callable $verifierValidator): self
    {
        if (Binary::safeStrlen($token) < self::TOKEN_CHAR_LENGTH) {
            // Don't zero memory as the value is invalid.
            throw new \RuntimeException('Invalid token provided.');
        }

        $instance = new self();
        $instance->token = new HiddenString($token);
        $instance->selector = Binary::safeSubstr($token, 0, 32);
        $instance->verifier = Binary::safeSubstr($token, 32);
        $instance->verifierValidator = $verifierValidator;
        $instance->verifierHasher = $verifierHasher;

        // Don't (re)generate as this needs the salt of the stored hash.
        $instance->verifierHash = null;

        \sodium_memzero($token);

        return $instance;
    }

    /**
     * Returns the selector to identify the token in storage.
     *
     * @return string
     */
    public function selector(): string
    {
        return $this->selector;
    }

    /**
     * Returns the token (selector + verifier) for authentication.
     *
     * @return HiddenString
     */
    public function token(): HiddenString
    {
        return $this->token;
    }

    /**
     * Verifies this SplitToken against a (stored) SplitTokenValueHolder.
     *
     * This method is to be used once the SplitToken is reconstructed
     * from a user-provided string.
     *
     * @param SplitTokenValueHolder $token
     * @param null|string           $id    Id this token was bound to during generation
     *
     * @return bool
     */
    public function matches(SplitTokenValueHolder $token, ?string $id = null): bool
    {
        if ($token->isExpired() || $token->selector() !== $this->selector) {
            return false;
        }

        return ($this->verifierValidator)($token->verifierHash(), $this->verifier.':'.($id ?? '\0'));
    }

    /**
     * Produce a new SplitTokenValue instance.
     *
     * Note: This method doesn't work when reconstructed from a string.
     *
     * @param array $metadata
     *
     * @return SplitTokenValueHolder
     */
    public function toValueHolder(array $metadata = []): SplitTokenValueHolder
    {
        if (null === $this->verifierHash) {
            throw new \RuntimeException('toValueHolder() does not work SplitToken object created with fromString().');
        }

        return new SplitTokenValueHolder($this->selector, $this->verifierHash, $this->expiresAt, $metadata);
    }
}
