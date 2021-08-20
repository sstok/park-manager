<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\User;

use Assert\Assertion;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Symfony\Component\Intl\Locales;

#[Embeddable]
final class UserPreferences
{
    #[Column(name: 'password_reset_enabled', type: 'boolean')]
    public bool $passwordResetEnabled = true;

    #[Column(name: 'locale', type: 'string', length: 5, nullable: true)]
    public ?string $locale = null;

    public function disablePasswordReset(): void
    {
        $this->passwordResetEnabled = false;
    }

    public function enablePasswordReset(): void
    {
        $this->passwordResetEnabled = true;
    }

    public function setLocale(?string $locale): void
    {
        if ($locale !== null) {
            Assertion::true(Locales::exists($locale), 'This value is not a valid locale.', 'locale');
        }

        $this->locale = $locale;
    }
}
