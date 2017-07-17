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

namespace ParkManager\Bundle\UserBundle\Form\Type;

use ParkManager\Component\Security\Token\SplitToken;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
class ConfirmPasswordResetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'password_not_the_same',
                'options' => [
                    'trim' => true,
                    'required' => true,
                    'constraints' => $options['password_constraints'] ?? [],
                    'attr' => [
                        'autocomplete' => 'off',
                        'autocorrect' => 'off',
                        'autocapitalize' => 'off',
                        'spellcheck' => 'false',
                    ],
                ],
                'first_options' => ['label' => 'label.password'],
                'second_options' => ['label' => 'label.password2'],
            ])
            ->add('reset_token', HiddenType::class, ['data' => $options['token']->token()])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('token')
            ->setDefault('password_constraints', [])
            ->setAllowedTypes('token', [SplitToken::class])
            ->setAllowedTypes('password_constraints', ['array', Constraint::class, null])
        ;
    }

    public function getBlockPrefix(): ?string
    {
        return 'confirm_user_password_reset';
    }
}
