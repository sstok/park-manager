<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\User;

use Lifthill\Bridge\Web\Form\Type\MessageFormType;
use ParkManager\Application\Command\Administrator\RegisterAdministrator;
use ParkManager\Application\Command\User\RegisterUser;
use ParkManager\Domain\User\Exception\EmailAddressAlreadyInUse;
use ParkManager\Domain\User\UserId;
use ParkManager\UI\Web\Form\Type\Security\SecurityUserHashedPasswordType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RegisterUserForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('display_name', TextType::class, [
                'label' => 'label.display_name',
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 2, 'max' => 60]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'label.email',
                'help' => 'help.register_user.email',
                'transform_to_model' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 3, 'max' => 320]),
                ],
            ])
            ->add('password', SecurityUserHashedPasswordType::class, [
                'label' => 'label.password',
                'password_constraints' => [
                    new NotBlank(),
                    new Length(['min' => 6, 'max' => 20]), // this is a temporary password, but still should be relatively secure
                ],
            ])
            ->add('is_admin', CheckboxType::class, ['label' => 'label.is_admin', 'help' => 'help.is_admin', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['user_id'])
            ->setAllowedTypes('user_id', UserId::class)
            ->setDefaults([
                'disable_entity_mapping' => true,
                'command_factory' => static function (array $fields, FormInterface $form) {
                    $id = $form->getConfig()->getOption('user_id');

                    if ($fields['is_admin'] ?? false) {
                        $user = new RegisterAdministrator(
                            $id,
                            $fields['email'],
                            $fields['display_name'],
                            $fields['password']
                        );
                    } else {
                        $user = new RegisterUser(
                            $id,
                            $fields['email'],
                            $fields['display_name'],
                            $fields['password']
                        );
                    }

                    $user->requireNewPassword();

                    return $user;
                },
                'exception_mapping' => [
                    EmailAddressAlreadyInUse::class => static function (EmailAddressAlreadyInUse $e, TranslatorInterface $translator): array {
                        $translatorId = $e->getTranslatorMsg();

                        return [
                            'email' => new FormError(
                                $translatorId->trans($translator),
                                $translatorId->getMessage(),
                                $translatorId->getParameters(),
                                null,
                                $e
                            ),
                        ];
                    },
                ],
            ]);
    }

    public function getBlockPrefix(): string
    {
        return 'register_user';
    }

    public function getParent(): string
    {
        return MessageFormType::class;
    }
}
