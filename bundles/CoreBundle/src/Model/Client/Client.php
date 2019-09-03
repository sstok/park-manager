<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Model\Client;

use Assert\Assertion;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ParkManager\Bundle\CoreBundle\Model\Client\Event\ClientEmailAddressChangeWasRequested;
use ParkManager\Bundle\CoreBundle\Model\Client\Event\ClientNameWasChanged;
use ParkManager\Bundle\CoreBundle\Model\Client\Event\ClientPasswordResetWasRequested;
use ParkManager\Bundle\CoreBundle\Model\Client\Event\ClientPasswordWasChanged;
use ParkManager\Bundle\CoreBundle\Model\Client\Event\ClientWasRegistered;
use ParkManager\Bundle\CoreBundle\Model\Client\Exception\EmailChangeConfirmationRejected;
use ParkManager\Bundle\CoreBundle\Model\Client\Exception\PasswordResetConfirmationRejected;
use ParkManager\Bundle\CoreBundle\Model\DomainEventsCollectionTrait;
use ParkManager\Bundle\CoreBundle\Model\EmailAddress;
use ParkManager\Bundle\CoreBundle\Model\RecordsDomainEvents;
use ParkManager\Bundle\CoreBundle\Security\ClientUser;
use ParkManager\Bundle\CoreBundle\Security\SecurityUser;
use Rollerworks\Component\SplitToken\SplitToken;
use Rollerworks\Component\SplitToken\SplitTokenValueHolder;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="client",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="client_email_address_uniq", columns={"email_address"}),
 *         @ORM\UniqueConstraint(name="client_email_canonical_uniq", columns={"email_canonical"}),
 *     }
 * )
 */
class Client implements RecordsDomainEvents
{
    use DomainEventsCollectionTrait;

    public const DEFAULT_ROLES = ['ROLE_USER'];

    /**
     * @ORM\Id()
     * @ORM\Column(type="park_manager_client_id")
     * @ORM\GeneratedValue(strategy="NONE")
     *
     * @var ClientId
     */
    protected $id;

    /**
     * @ORM\Embedded(class="ParkManager\Bundle\CoreBundle\Model\EmailAddress", columnPrefix="email_")
     *
     * @var EmailAddress
     */
    protected $email;

    /**
     * @ORM\Column(name="display_name", type="string")
     *
     * @var string
     */
    protected $displayName;

    /**
     * @ORM\Column(name="login_enabled", type="boolean")
     *
     * @var bool
     */
    protected $loginEnabled = true;

    /**
     * @ORM\Column(type="array_collection")
     *
     * @var Collection
     */
    protected $roles;

    /**
     * @ORM\Embedded(class="Rollerworks\Component\SplitToken\SplitTokenValueHolder", columnPrefix="email_change_")
     *
     * @var SplitTokenValueHolder|null
     */
    protected $emailAddressChangeToken;

    /**
     * @ORM\Column(name="auth_password", type="text", nullable=true)
     *
     * @var string|null
     */
    protected $password;

    /**
     * @ORM\Column(name="password_reset_enabled", type="boolean")
     *
     * @var bool
     */
    protected $passwordResetEnabled = true;

    /**
     * @ORM\Embedded(class="Rollerworks\Component\SplitToken\SplitTokenValueHolder", columnPrefix="password_reset_")
     *
     * @var SplitTokenValueHolder|null
     */
    protected $passwordResetToken;

    protected function __construct(ClientId $id, EmailAddress $email, string $displayName)
    {
        $this->id          = $id;
        $this->email       = $email;
        $this->displayName = $displayName;
        $this->roles       = new ArrayCollection(static::DEFAULT_ROLES);
    }

    public static function register(ClientId $id, EmailAddress $email, string $displayName, ?string $password = null): self
    {
        $client = new static($id, $email, $displayName);
        $client->recordThat(new ClientWasRegistered($id, $email, $displayName));
        $client->changePassword($password);

        return $client;
    }

    public function getId(): ClientId
    {
        return $this->id;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getEmail(): EmailAddress
    {
        return $this->email;
    }

    public function changeEmail(EmailAddress $email): void
    {
        $this->email = $email;
    }

    public function isEnabled(): bool
    {
        return $this->loginEnabled;
    }

    public function disable(): void
    {
        $this->loginEnabled = false;
    }

    public function enable(): void
    {
        $this->loginEnabled = true;
    }

    public function requestEmailChange(EmailAddress $email, SplitToken $token): bool
    {
        if (! SplitTokenValueHolder::mayReplaceCurrentToken($this->emailAddressChangeToken, ['email' => $email->address])) {
            return false;
        }

        $this->emailAddressChangeToken = $token->toValueHolder()->withMetadata(['email' => $email->address]);
        $this->recordThat(new ClientEmailAddressChangeWasRequested($this->id, $token, $email));

        return true;
    }

    public function confirmEmailChange(SplitToken $token): void
    {
        try {
            if (! $token->matches($this->emailAddressChangeToken)) {
                throw new EmailChangeConfirmationRejected();
            }

            /** @psalm-suppress PossiblyNullReference */
            $this->changeEmail(new EmailAddress($this->emailAddressChangeToken->metadata()['email']));
        } finally {
            $this->emailAddressChangeToken = null;
        }
    }

    public function changeName(string $displayName): void
    {
        if ($this->displayName !== $displayName) {
            $this->recordThat(new ClientNameWasChanged($this->id, $displayName));
            $this->displayName = $displayName;
        }
    }

    /**
     * Pass null When another authentication system is used.
     */
    public function changePassword(?string $password): void
    {
        if ($password !== null) {
            Assertion::notEmpty($password, 'Password can only null or a non-empty string.');
        }

        if ($this->password !== $password) {
            $this->password = $password;

            $this->recordThat(new ClientPasswordWasChanged($this->id, $password));
        }
    }

    /**
     * @return bool false when a token was already set _and_ not expired,
     *              or when password resetting was disabled for this client.
     *              True when the token was accepted and set
     */
    public function requestPasswordReset(SplitToken $token): bool
    {
        if (! $this->passwordResetEnabled) {
            return false;
        }

        if (! SplitTokenValueHolder::mayReplaceCurrentToken($this->passwordResetToken)) {
            return false;
        }

        $this->passwordResetToken = $token->toValueHolder();
        $this->recordThat(new ClientPasswordResetWasRequested($this->id, $token));

        return true;
    }

    public function confirmPasswordReset(SplitToken $token, string $newPassword): void
    {
        if (! $this->passwordResetEnabled) {
            return;
        }

        try {
            if (! $token->matches($this->passwordResetToken)) {
                throw new PasswordResetConfirmationRejected();
            }

            $this->changePassword($newPassword);
        } finally {
            $this->clearPasswordReset();
        }
    }

    public function clearPasswordReset(): void
    {
        $this->passwordResetToken = null;
    }

    public function disablePasswordReset(): void
    {
        $this->passwordResetEnabled = false;
        $this->passwordResetToken   = null;
    }

    public function enablePasswordReset(): void
    {
        $this->passwordResetEnabled = true;
    }

    public function getPasswordResetToken(): ?SplitTokenValueHolder
    {
        return $this->passwordResetToken;
    }

    public function toSecurityUser(): SecurityUser
    {
        return new ClientUser($this->id->toString(), $this->password ?? '', $this->loginEnabled, $this->roles->toArray());
    }
}
