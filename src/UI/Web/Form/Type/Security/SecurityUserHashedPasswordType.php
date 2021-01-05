<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Security;

use ParkManager\Infrastructure\Security\SecurityUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface as EncoderFactory;
use function Sodium\memzero;

final class SecurityUserHashedPasswordType extends AbstractType
{
    private EncoderFactory $encoderFactory;

    public function __construct(EncoderFactory $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('algorithm', function (Options $options) {
                return function (string $value) {
                    $encoded = $this->encoderFactory->getEncoder(SecurityUser::class)->encodePassword($value, null);

                    memzero($value);

                    return $encoded;
                };
            });
    }

    public function getParent(): ?string
    {
        return HashedPasswordType::class;
    }
}
