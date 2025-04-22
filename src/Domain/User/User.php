<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\User;

use Assert\Assertion;
use Carbon\CarbonImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Lifthill\Component\Common\Domain\Attribute\Entity as DomainEntity;
use Lifthill\Component\Common\Domain\Model\EmailAddress;
use ParkManager\Domain\Exception\PasswordResetTokenNotAccepted;
use ParkManager\Domain\TimestampableTrait;
use ParkManager\Domain\User\Exception\CannotDisableSuperAdministrator;
use ParkManager\Domain\User\Exception\CannotMakeUserSuperAdmin;
use ParkManager\Domain\User\Exception\EmailChangeConfirmationRejected;
use ParkManager\Infrastructure\Security\SecurityUser;
use Rollerworks\Component\SplitToken\SplitToken;
use Rollerworks\Component\SplitToken\SplitTokenValueHolder;

#[Entity]
#[Table(name: 'app_user')]
#[UniqueConstraint(name: 'user_email_address_uniq', columns: ['email_address'])]
#[UniqueConstraint(name: 'user_email_canonical_uniq', columns: ['email_canonical'])]
#[DomainEntity]
class User implements \Stringable
{
    use TimestampableTrait;

    public const DEFAULT_ROLES = ['ROLE_USER'];

    #[Column(name: 'login_enabled', type: 'boolean')]
    public bool $loginEnabled = true;

    /**
     * @var Collection<int, string>
     */
    #[Column(type: 'array_collection')]
    public Collection $roles;

    #[Embedded(class: SplitTokenValueHolder::class, columnPrefix: 'email_change_')]
    public ?SplitTokenValueHolder $emailAddressChangeToken = null;

    #[Column(name: 'password_expiration', type: 'carbon_immutable', nullable: true)]
    public ?CarbonImmutable $passwordExpiresOn = null;

    #[Embedded(class: SplitTokenValueHolder::class, columnPrefix: 'password_reset_')]
    public ?SplitTokenValueHolder $passwordResetToken = null;

    #[Embedded(class: UserPreferences::class, columnPrefix: 'preference_')]
    public UserPreferences $preferences;

    #[Column(name: 'postal_code', type: 'lifthill_encrypted:text;security_level_c2', nullable: true)]
    public ?string $postalCode = null;

    private function __construct(
        #[Id]
        #[Column(type: 'park_manager_user_id')]
        #[GeneratedValue(strategy: 'NONE')]
        public UserId $id,

        #[Embedded(class: EmailAddress::class, columnPrefix: 'email_')]
        public EmailAddress $email,

        #[Column(name: 'display_name', type: 'string')]
        public string $displayName,

        #[Column(name: 'auth_password', type: 'text')]
        public string $password
    ) {
        Assertion::false($email->isPattern, 'Email cannot be a pattern.', 'email');

        $this->roles = new ArrayCollection(static::DEFAULT_ROLES);
        $this->preferences = new UserPreferences();
    }

    public static function register(UserId $id, EmailAddress $email, string $displayName, string $password): self
    {
        return new self($id, $email, $displayName, $password);
    }

    public static function registerAdmin(UserId $id, EmailAddress $email, string $displayName, string $password): self
    {
        $user = new self($id, $email, $displayName, $password);
        $user->addRole('ROLE_ADMIN');

        return $user;
    }

    public function changeEmail(EmailAddress $email): void
    {
        Assertion::false($email->isPattern, 'Email cannot be a pattern.', 'email');

        $this->email = $email;
        $this->emailAddressChangeToken = null;
    }

    public function disableLogin(): void
    {
        if ($this->hasRole('ROLE_SUPER_ADMIN')) {
            throw new CannotDisableSuperAdministrator($this->id);
        }

        $this->loginEnabled = false;
    }

    public function enableLogin(): void
    {
        $this->loginEnabled = true;
    }

    public function expirePasswordOn(?CarbonImmutable $dateTime): void
    {
        $this->passwordExpiresOn = $dateTime;
    }

    /**
     * @return array<int, string>
     */
    public function getRoles(): array
    {
        return $this->roles->getValues();
    }

    public function addRole(string $role): void
    {
        if ($this->roles->contains($role)) {
            return;
        }

        if ($role === 'ROLE_SUPER_ADMIN' && ! $this->hasRole('ROLE_ADMIN')) {
            throw new CannotMakeUserSuperAdmin();
        }

        $this->roles = clone $this->roles;
        $this->roles->add($role);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains($role);
    }

    public function removeRole(string $role): void
    {
        Assertion::notInArray($role, self::DEFAULT_ROLES, 'Cannot remove default role "' . $role . '".');

        if ($role === 'ROLE_ADMIN' && $this->hasRole('ROLE_SUPER_ADMIN')) {
            throw new CannotDisableSuperAdministrator($this->id);
        }

        $this->roles = clone $this->roles;
        $this->roles->removeElement($role);
    }

    public function requestEmailChange(EmailAddress $email, SplitToken $token): bool
    {
        if (! SplitTokenValueHolder::mayReplaceCurrentToken($this->emailAddressChangeToken, ['email' => $email->address])) {
            return false;
        }

        $this->emailAddressChangeToken = $token->toValueHolder()->withMetadata(['email' => $email->address]);

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
        $this->displayName = $displayName;
    }

    public function changePassword(string $password): void
    {
        Assertion::notEmpty($password, 'Password cannot be empty.', 'password');

        $this->password = $password;
        $this->passwordExpiresOn = null;
    }

    /**
     * @return bool false when a token was already set _and_ not expired,
     *              or when password resetting was disabled for this user.
     *              True when the token was accepted and set
     */
    public function requestPasswordReset(SplitToken $token): bool
    {
        if (! $this->preferences->passwordResetEnabled) {
            return false;
        }

        if (! SplitTokenValueHolder::mayReplaceCurrentToken($this->passwordResetToken)) {
            return false;
        }

        $this->passwordResetToken = $token->toValueHolder();

        return true;
    }

    public function confirmPasswordReset(SplitToken $token, string $newPassword): void
    {
        if (! $this->preferences->passwordResetEnabled) {
            return;
        }

        try {
            if (! $token->matches($this->passwordResetToken)) {
                throw new PasswordResetTokenNotAccepted($this->passwordResetToken, $token);
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

    public function toSecurityUser(): SecurityUser
    {
        return new SecurityUser($this->id->toString(), $this->password, $this->loginEnabled, $this->getRoles());
    }

    public function __toString(): string
    {
        return $this->id->toString();
    }

    public function isPasswordExpired(): bool
    {
        if ($this->passwordExpiresOn === null) {
            return false;
        }

        return $this->passwordExpiresOn->isPast();
    }

    public function hasPasswordResetPending(): bool
    {
        return $this->passwordResetToken !== null && ! $this->passwordResetToken->isExpired(CarbonImmutable::now());
    }

    public function changePostalCode(?string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }
}
