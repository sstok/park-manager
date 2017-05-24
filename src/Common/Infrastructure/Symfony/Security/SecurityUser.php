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

namespace ParkManager\Common\Infrastructure\Symfony\Security;

use ParkManager\Common\Model\Security\AuthenticationInfo;
use ParkManager\Common\Model\Security\EmailAddressAndPasswordAuthentication;
use ParkManager\Common\Projection\UserReadModel;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
class SecurityUser implements UserInterface, EquatableInterface, \Serializable
{
    protected $authentication;
    protected $username;
    protected $password = '';
    protected $roles;
    protected $enabled;

    public function __construct(UserReadModel $user)
    {
        $this->authentication = $user->authenticationInfo();
        $this->enabled = $user->isAccessEnabled();
        $this->username = (string) $user->id();
        $this->roles = ['ROLE_USER'];

        if ($this->authentication instanceof EmailAddressAndPasswordAuthentication) {
            $this->password = (string) $this->authentication->password();
        }
    }

    public function serialize(): string
    {
        // ID (username), password, IS_SUPER_ADMIN, Roles, enabled, (password) expired
        return serialize([
            'authentication' => $this->getAuthentication(),
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
            'roles' => $this->getRoles(),
            'enabled' => $this->enabled,
        ]);
    }

    public function unserialize($serialized): void
    {
        $data = unserialize($serialized, []);

        $this->authentication = $data['authentication'];
        $this->username = $data['username'];
        $this->password = $data['password'];
        $this->roles = $data['roles'];
        $this->enabled = $data['enabled'];
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

    public function getAuthentication(): AuthenticationInfo
    {
        return $this->authentication;
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
        if (!$user instanceof static) {
            return false;
        }

        if (!$user->getAuthentication()->equals($this->getAuthentication())) {
            return false;
        }

        if ($user->getRoles() !== $this->getRoles()) {
            return false;
        }

        if ($user->isEnabled() !== $this->isEnabled()) {
            return false;
        }

        return true;
    }
}
