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

/**
 * SplitTokenValue allows to easily store the SplitToken information in a database.
 *
 * A SplitTokenValue holds the SplitToken:
 *
 * * selector;
 * * verifier-hash;
 * * an (optional) expiration timestamp;
 * * And (optionally) some metadata to perform the operation;
 *
 * The original token is not stored with this value-object.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class SplitTokenValueHolder
{
    private $selector;
    private $verifierHash;
    private $expiresAt;
    private $metadata = [];

    public function __construct(
        string $selector,
        string $verifierHash,
        ?\DateTimeImmutable $expiresAt = null,
        array $metadata = []
    ) {
        $this->selector = $selector;
        $this->verifierHash = $verifierHash;
        $this->expiresAt = $expiresAt;
        $this->metadata = $metadata;
    }

    /**
     * Returns whether the provided value-holder is empty.
     *
     * @param SplitTokenValueHolder|null $valueHolder
     *
     * @return bool
     */
    public static function isEmpty(?self $valueHolder): bool
    {
        if (null === $valueHolder) {
            return true;
        }

        return null === $valueHolder->selector;
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
        return $this->verifierHash;
    }

    public function withMetadata(?array $metadata): self
    {
        return new self($this->selector, $this->verifierHash, $this->expiresAt, $metadata);
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function isExpired(?\DateTimeImmutable $datetime = null): bool
    {
        if (null === $this->expiresAt) {
            return false;
        }

        return $this->expiresAt->getTimestamp() < ($datetime ?? new \DateTimeImmutable())->getTimestamp();
    }

    public function isValid(SplitToken $token, ?string $id = null): bool
    {
        return !$this->isExpired() && $token->matches($this->selector(), $this->verifierHash(), $id);
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function expiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
