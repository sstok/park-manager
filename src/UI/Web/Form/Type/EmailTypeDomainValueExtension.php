<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type;

use ParkManager\Domain\EmailAddress;
use ParkManager\Domain\Exception\MalformedEmailAddress;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EmailTypeDomainValueExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (! $options['transform_to_model']) {
            return;
        }

        $builder->addModelTransformer(new CallbackTransformer(
            static function ($value): string {
                if ($value === '' || $value === null) {
                    return '';
                }

                if (\is_string($value)) {
                    return $value;
                }

                if ($value instanceof EmailAddress) {
                    return $value->toString();
                }

                throw new UnexpectedTypeException($value, EmailAddress::class . '" or "string');
            },
            static function ($value): ?EmailAddress {
                if ($value === '') {
                    return null;
                }

                try {
                    return new EmailAddress($value);
                } catch (MalformedEmailAddress $e) {
                    throw new TransformationFailedException('Invalid Email address provided', 0, $e);
                }
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('transform_to_model', false)
            ->setAllowedTypes('transform_to_model', ['bool'])
        ;
    }

    public static function getExtendedTypes(): iterable
    {
        return [EmailType::class];
    }
}
