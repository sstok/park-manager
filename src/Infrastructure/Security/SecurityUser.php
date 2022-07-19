<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Security;

use ParkManager\Domain\User\User;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The SecurityUser wraps around a User-model and keeps only
 * the information related to authentication.
 */
final class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface
{
    private string $id;
    private string $password;

    /** @var array<int, string> */
    private array $roles;
    private bool $enabled;

    /**
     * @param array<int, string> $roles
     */
    public function __construct(string $id, string $password, bool $enabled, array $roles)
    {
        sort($roles, \SORT_STRING);

        $this->id = $id;
        $this->password = $password;
        $this->enabled = $enabled;
        $this->roles = $roles;
    }

    public static function fromEntity(User $user): self
    {
        return $user->toSecurityUser();
    }

    /**
     * @return array<int, string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getSalt(): ?string
    {
        return null; // No-op
    }

    public function getUsername(): string
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isAccountExpired(): bool
    {
        return false;
    }

    public function isCredentialsExpired(): bool
    {
        return false;
    }

    public function eraseCredentials(): void
    {
        // no-op
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if (! $user instanceof self) {
            return false;
        }

        // Should never mismatch, this is a safety precaution against a broken user-provider.
        if ($user->getUserIdentifier() !== $this->getUserIdentifier()) {
            return false;
        }

        if ($user->getPassword() !== $this->getPassword()) {
            return false;
        }

        if ($user->getRoles() !== $this->getRoles()) {
            return false;
        }

        return $user->isEnabled() === $this->isEnabled();
    }

    public function isAdmin(): bool
    {
        return \in_array('ROLE_ADMIN', $this->getRoles(), true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->isAdmin() && \in_array('ROLE_SUPER_ADMIN', $this->getRoles(), true);
    }
}
