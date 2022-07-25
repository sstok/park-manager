<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form\Type\Mocks;

use ParkManager\Infrastructure\Security\SecurityUser;
use RuntimeException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherAwareInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/** @internal */
final class FakePasswordHasherFactory implements PasswordHasherFactoryInterface
{
    private object $hasher;
    private string $userClass;

    public function __construct()
    {
        $this->userClass = SecurityUser::class;
        $this->hasher = new class() implements PasswordHasherInterface {
            public function hash(string $plainPassword): string
            {
                return 'encoded(' . $plainPassword . ')';
            }

            public function verify(string $hashedPassword, string $plainPassword): bool
            {
                return false;
            }

            public function needsRehash(string $hashedPassword): bool
            {
                return false;
            }
        };
    }

    public function getPasswordHasher(string | PasswordAuthenticatedUserInterface | PasswordHasherAwareInterface $user): PasswordHasherInterface
    {
        if ($user !== $this->userClass) {
            throw new RuntimeException('Nope, that is not the right user.');
        }

        return $this->hasher;
    }
}
