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

use ParkManager\Component\User\Model\User;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The SecurityUser wraps around a "regular" a User and
 * keeps only the information related to authentication.
 *
 * To ensure password-encoders work properly this class needs
 * to be extended for each each "user type".
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
abstract class SecurityUser implements UserInterface, EquatableInterface, \Serializable
{
    protected $username;
    protected $password;
    protected $roles;
    protected $enabled;

    public function __construct(string $id, string $password, bool $enabled, array $roles)
    {
        $this->username = $id;
        $this->password = $password;
        $this->enabled = $enabled;
        $this->roles = $roles;
    }

    public function serialize(): string
    {
        return serialize([
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
            'enabled' => $this->enabled,
            'roles' => $this->getRoles(),
        ]);
    }

    public function unserialize($serialized): void
    {
        $data = unserialize($serialized, ['allowed_classes' => false]);

        $this->username = $data['username'];
        $this->password = $data['password'];
        $this->enabled = $data['enabled'];
        $this->roles = $data['roles'];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @ignore
     * @codeCoverageIgnore
     */
    public function getSalt()
    {
        return null; // No-op
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     */
    public function id(): string
    {
        return $this->username;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function eraseCredentials(): void
    {
        // no-op
    }

    /**
     * {@inheritdoc}
     */
    public function isEqualTo(UserInterface $user): bool
    {
        if (get_class($user) !== get_class($this)) {
            return false;
        }

        // Should never mismatch, this is a safety precaution against a broken user-provider.
        if ($user->getUsername() !== $this->getUsername()) {
            return false;
        }

        if ($user->getRoles() !== $this->getRoles()) {
            return false;
        }

        if (!hash_equals($user->getPassword(), $this->getPassword())) {
            return false;
        }

        /* @var User $user */
        if ($user->isEnabled() !== $this->isEnabled()) {
            return false;
        }

        return true;
    }
}
