<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Model\Administrator;

use Assert\Assertion;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ParkManager\Bundle\CoreBundle\Model\Administrator\Exception\CannotDisableSuperAdministrator;
use ParkManager\Bundle\CoreBundle\Model\EmailAddress;
use ParkManager\Bundle\CoreBundle\Security\AdministratorUser;
use ParkManager\Bundle\CoreBundle\Security\SecurityUser;
use Rollerworks\Component\SplitToken\SplitToken;
use Rollerworks\Component\SplitToken\SplitTokenValueHolder;

/**
 * @ORM\Entity
 * @ORM\Table(name="administrator",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="administrator_email_address_uniq", columns={"email_address"}),
 *         @ORM\UniqueConstraint(name="administrator_email_canonical_uniq", columns={"email_canonical"}),
 *     }
 * )
 *
 * @final
 */
class Administrator
{
    /**
     * @ORM\Id
     * @ORM\Column(type="park_manager_administrator_id")
     * @ORM\GeneratedValue(strategy="NONE")
     *
     * @var AdministratorId
     */
    private $id;

    /**
     * @ORM\Embedded(class="ParkManager\Bundle\CoreBundle\Model\EmailAddress", columnPrefix="email_")
     *
     * @var EmailAddress
     */
    private $email;

    /**
     * @ORM\Column(name="display_name", type="string")
     *
     * @var string
     */
    private $displayName;

    /**
     * @ORM\Column(name="login_enabled", type="boolean")
     *
     * @var bool
     */
    private $loginEnabled = true;

    /**
     * @ORM\Column(type="array_collection")
     *
     * @var Collection
     */
    private $roles;

    /**
     * @ORM\Column(name="auth_password", type="text", nullable=true)
     *
     * @var string|null
     */
    private $password;

    /**
     * @ORM\Embedded(class="Rollerworks\Component\SplitToken\SplitTokenValueHolder", columnPrefix="password_reset_")
     *
     * @var SplitTokenValueHolder|null
     */
    private $passwordResetToken;

    public const DEFAULT_ROLES = ['ROLE_ADMIN'];

    private function __construct(AdministratorId $id, EmailAddress $email, string $displayName)
    {
        $this->id = $id;
        $this->email = $email;
        $this->roles = new ArrayCollection(self::DEFAULT_ROLES);
        $this->displayName = $displayName;
    }

    public static function register(AdministratorId $id, EmailAddress $email, string $displayName, ?string $password = null): self
    {
        $user = new self($id, $email, $displayName);
        $user->changePassword($password);

        return $user;
    }

    public function getId(): AdministratorId
    {
        return $this->id;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getEmailAddress(): EmailAddress
    {
        return $this->email;
    }

    public function changeEmail(EmailAddress $email): void
    {
        $this->email = $email;
    }

    public function changeName(string $displayName): void
    {
        $this->displayName = $displayName;
    }

    public function isLoginEnabled(): bool
    {
        return $this->loginEnabled;
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

    /**
     * @return string[]
     */
    public function getRoles(): iterable
    {
        return $this->roles->toArray();
    }

    public function addRole(string $role): void
    {
        if (! $this->roles->contains($role)) {
            $this->roles->add($role);
        }
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains($role);
    }

    public function removeRole(string $role): void
    {
        Assertion::notInArray($role, self::DEFAULT_ROLES, 'Cannot remove default role "' . $role . '".');

        $this->roles->removeElement($role);
    }

    /**
     * Pass null When another authentication system is used.
     */
    public function changePassword(?string $password): void
    {
        if ($password !== null) {
            Assertion::notEmpty($password, 'Password can only null or a non-empty string.');
        }

        $this->password = $password;
    }

    public function requestPasswordReset(SplitToken $token): bool
    {
        $this->passwordResetToken = $token->toValueHolder();

        return true;
    }

    /**
     * Tries to confirm password resetting.
     *
     * Note: Make sure to always store the Entity after calling this method.
     * Even if this method returned false.
     *
     * When the confirmation was successful this updates the password of the user.
     * When the user is disabled this still returns true and continues.
     * When the token doesn't match, it's removed. We do not allow a second chance.
     *
     * @return bool Returns true when the reset was accepted, false otherwise (token invalid/expired)
     */
    public function confirmPasswordReset(SplitToken $token, string $newPassword): bool
    {
        if (SplitTokenValueHolder::isEmpty($this->passwordResetToken)) {
            $this->passwordResetToken = null;

            return false;
        }

        try {
            if ($token->matches($this->passwordResetToken)) {
                $this->changePassword($newPassword);

                return true;
            }

            return false;
        } finally {
            $this->passwordResetToken = null;
        }
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getPasswordResetToken(): ?SplitTokenValueHolder
    {
        return $this->passwordResetToken;
    }

    public function clearPasswordReset(): void
    {
        $this->passwordResetToken = null;
    }

    public function toSecurityUser(): SecurityUser
    {
        return new AdministratorUser($this->id->toString(), $this->password ?? '', $this->loginEnabled, $this->roles->toArray());
    }
}
