<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Security;

use Lifthill\Bridge\Web\Form\Type\HashedPasswordType;
use ParkManager\Infrastructure\Security\SecurityUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class SecurityUserHashedPasswordType extends AbstractType
{
    public function __construct(private PasswordHasherFactoryInterface $hasherFactory) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('algorithm', function (Options $options) {
                return function (string $value) {
                    $hashed = $this->hasherFactory->getPasswordHasher(SecurityUser::class)->hash($value);

                    \sodium_memzero($value);

                    return $hashed;
                };
            });
    }

    public function getParent(): ?string
    {
        return HashedPasswordType::class;
    }
}
