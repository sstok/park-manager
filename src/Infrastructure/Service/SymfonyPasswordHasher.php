<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Service;

use ParagonIE\HiddenString\HiddenString;
use ParkManager\Application\Service\PasswordHasher;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use function Sodium\memzero;

final class SymfonyPasswordHasher implements PasswordHasher
{
    public function __construct(private PasswordHasherInterface $hasher)
    {
    }

    public function hash(HiddenString $password): string
    {
        $plainPassword = $password->getString();
        $hashedPassword = $this->hasher->hash($password->getString());

        memzero($plainPassword);

        return $hashedPassword;
    }
}
