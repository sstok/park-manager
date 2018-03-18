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

namespace ParkManager\Component\User\Model;

use Assert\Assertion;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ParkManager\Component\Model\EventsRecordingEntity;
use ParkManager\Component\Security\Token\SplitToken;
use ParkManager\Component\Security\Token\SplitTokenValueHolder;
use ParkManager\Component\User\Model\Event\UserPasswordWasChanged;

/**
 * A User is a uniquely identifiable identity of one person.
 *
 * Extend this class for more specific user types (admin/customer).
 *
 * Note: This object should not be stored in a session directly
 * but instead be used as information provider for a SecurityUser.
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
abstract class User extends EventsRecordingEntity
{
    public const DEFAULT_ROLE = 'ROLE_USER';

    /**
     * @var UserId
     */
    protected $id;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $canonicalEmail;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var string|null
     */
    protected $password;

    /**
     * @var Collection
     */
    protected $roles;

    /**
     * @var SplitTokenValueHolder|null
     */
    protected $emailAddressChangeToken;

    /**
     * @var SplitTokenValueHolder|null
     */
    protected $passwordResetToken;

    protected function __construct(UserId $id, string $email, string $canonicalEmail)
    {
        $this->id = $id;
        $this->email = $email;
        $this->canonicalEmail = $canonicalEmail;
        $this->roles = new ArrayCollection(static::getDefaultRoles());
    }

    public function id(): UserId
    {
        return $this->id;
    }

    /**
     * Email Address of the user as originally provided.
     *
     * @return string
     */
    public function email(): string
    {
        return $this->email;
    }

    /**
     * Canonical EmailAddress of the user.
     *
     * A canonical e-mail address eg. has casing normalized.
     * All comments stripped, international to puny-code (etc).
     *
     * @return string
     */
    public function canonicalEmail(): string
    {
        return $this->canonicalEmail;
    }

    public function changeEmail(string $email, string $canonicalEmail): void
    {
        $this->email = $email;
        $this->canonicalEmail = $canonicalEmail;
    }

    /**
     * Returns the hashed password.
     *
     * When empty a different authentication type is assumed.
     *
     * @return null|string
     */
    public function password(): ?string
    {
        return $this->password;
    }

    /**
     * Change the user's password.
     *
     * Pass null when another authentication system is used.
     *
     * @param null|string $password
     */
    public function changePassword(?string $password): void
    {
        if (null !== $password) {
            Assertion::notEmpty($password, 'Password can only null or a non-empty string.');
        }

        if ($this->password !== $password) {
            $this->password = $password;

            $this->recordThat(new UserPasswordWasChanged($this->id()));
        }
    }

    /**
     * Returns whether access are enabled (is also to login).
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Returns the roles granted to the user.
     *
     * @return string[]
     */
    public function roles(): array
    {
        return $this->roles->toArray();
    }

    public function addRole(string $role): void
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains($role);
    }

    public function removeRole(string $role): void
    {
        Assertion::notInArray($role, self::getDefaultRoles(), 'Cannot remove default role "'.$role.'".');

        $this->roles->removeElement($role);
    }

    /**
     * Set the confirmation of e-mail address change information.
     *
     * @param string                $email
     * @param string                $canonicalEmail
     * @param SplitTokenValueHolder $token
     *
     * @return bool Returns false when a not expired confirmation-token was already set (for this address)
     *              true when the token was accepted and set
     */
    public function setConfirmationOfEmailAddressChange(string $email, string $canonicalEmail, SplitTokenValueHolder $token): bool
    {
        if (!SplitTokenValueHolder::isEmpty($this->emailAddressChangeToken) && !$this->emailAddressChangeToken->isExpired()) {
            $metadata = $this->emailAddressChangeToken->metadata();
            if ($metadata['canonical_email'] === $canonicalEmail && $metadata['email'] === $email) {
                return false;
            }
        }

        $this->emailAddressChangeToken = $token->withMetadata(['email' => $email, 'canonical_email' => $canonicalEmail]);

        return true;
    }

    /**
     * Tries to confirm the change of the e-mail address.
     *
     * When the confirmation was successful this should update the e-mail address
     * of the user with the e-mail address stored by the request.
     *
     * Note: When the token doesn't match, remove it. Do not allow even a second chance.
     *
     * @param SplitToken $token
     *
     * @return bool Returns true when the confirmation was accepted, false otherwise (token invalid/expired)
     */
    public function confirmEmailAddressChange(SplitToken $token): bool
    {
        if (SplitTokenValueHolder::isEmpty($this->emailAddressChangeToken)) {
            return false;
        }

        try {
            if ($this->emailAddressChangeToken->isValid($token, $this->id()->toString())) {
                $metadata = $this->emailAddressChangeToken->metadata();
                $this->changeEmail($metadata['email'], $metadata['canonical_email']);

                return true;
            }

            return false;
        } finally {
            $this->emailAddressChangeToken = null;
        }
    }

    /**
     * Sets the password reset token (for confirmation).
     *
     * @param SplitTokenValueHolder $token
     *
     * @return bool Returns false when a not expired confirmation-token was already set or when
     *              password resetting was disabled for this user,
     *              true when the token was accepted and set
     */
    public function setPasswordResetToken(SplitTokenValueHolder $token): bool
    {
        if (!SplitTokenValueHolder::isEmpty($this->passwordResetToken) && !$this->passwordResetToken->isExpired()) {
            return false;
        }

        $this->passwordResetToken = $token;

        return true;
    }

    /**
     * Tries to confirm password resetting.
     *
     * When the confirmation was successful this should update the password of the user.
     * When the user is disabled this should still return true and continue.
     *
     * Note: When the token doesn't match, remove it. Do not allow even a second chance.
     *
     * @param SplitToken $token
     *
     * @return bool Returns true when the reset was accepted, false otherwise (token invalid/expired)
     */
    public function confirmPasswordReset(SplitToken $token, string $passwordHash): bool
    {
        if (SplitTokenValueHolder::isEmpty($this->passwordResetToken)) {
            return false;
        }

        try {
            if ($this->passwordResetToken->isValid($token, $this->id()->toString())) {
                $this->changePassword($passwordHash);

                return true;
            }

            return false;
        } finally {
            $this->passwordResetToken = null;
        }
    }

    public function passwordResetToken(): ?SplitTokenValueHolder
    {
        if (SplitTokenValueHolder::isEmpty($this->passwordResetToken) || $this->passwordResetToken->isExpired()) {
            return null;
        }

        return $this->passwordResetToken;
    }

    /**
     * @return array
     */
    protected static function getDefaultRoles(): array
    {
        return [self::DEFAULT_ROLE];
    }
}
