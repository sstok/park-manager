<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\User\Admin;

use ParkManager\Application\Command\User\GrantUserRole;
use ParkManager\Application\Command\User\RevokeUserRole;
use ParkManager\Domain\User\User;
use ParkManager\UI\Web\Form\Type\MessageFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserSecurityLevelForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('level', ChoiceType::class, [
                'expanded' => true,
                'choices' => [
                    'user_role.super_admin' => 'ROLE_SUPER_ADMIN',
                    'user_role.admin' => 'ROLE_ADMIN',
                    'user_role.user' => 'ROLE_USER',
                ],
                'data' => $options['selected_level'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['user'])
            ->setAllowedTypes('user', User::class)
            ->setDefaults([
                'disable_entity_mapping' => true,
                'selected_level' => static function (Options $options) {
                    /** @var User $user */
                    $user = $options['user'];

                    if ($user->hasRole('ROLE_SUPER_ADMIN')) {
                        return 'ROLE_SUPER_ADMIN';
                    }

                    if ($user->hasRole('ROLE_ADMIN')) {
                        return 'ROLE_ADMIN';
                    }

                    return 'ROLE_USER';
                },
                'command_factory' => static function (array $fields, FormInterface $form) {
                    $oldLevel = $form->getConfig()->getOption('selected_level');
                    $newLevel = $fields['level'];

                    if ($oldLevel === $newLevel) {
                        return;
                    }

                    /** @var User $user */
                    $user = $form->getConfig()->getOption('user');
                    $id = $user->id;

                    if ($newLevel === 'ROLE_SUPER_ADMIN') {
                        if ($oldLevel === 'ROLE_ADMIN') {
                            return new GrantUserRole($id, 'ROLE_SUPER_ADMIN');
                        }

                        return new GrantUserRole($id, 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN');
                    }

                    if ($newLevel === 'ROLE_ADMIN') {
                        if ($oldLevel === 'ROLE_SUPER_ADMIN') {
                            return new RevokeUserRole($id, 'ROLE_SUPER_ADMIN');
                        }

                        return new GrantUserRole($id, 'ROLE_ADMIN');
                    }

                    return new RevokeUserRole($id, 'ROLE_SUPER_ADMIN', 'ROLE_ADMIN');
                },
            ]);
    }

    public function getBlockPrefix(): string
    {
        return 'user_security_level';
    }

    public function getParent(): string
    {
        return MessageFormType::class;
    }
}
