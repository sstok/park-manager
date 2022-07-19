<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Security;

use ParkManager\Application\Command\User\ChangePassword;
use ParkManager\Infrastructure\Security\SecurityUser;
use ParkManager\UI\Web\Form\Model\CommandDto;
use ParkManager\UI\Web\Form\Type\MessageFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Validator\Constraint;
use function Sodium\memzero;

final class ChangePasswordType extends AbstractType
{
    public function __construct(private PasswordHasherFactoryInterface $encoderFactory)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
            $data = $event->getData();

            if (! \is_array($data)) {
                $event->setData(['id' => (string) $data]);
            }
        }, 100);

        $builder
            ->add('password', HashedPasswordType::class, [
                'required' => true,
                'password_confirm' => true,
                'password_options' => [
                    'constraints' => $options['password_constraints'],
                ],
                'algorithm' => function (string $value) {
                    $hashed = $this->encoderFactory->getPasswordHasher(SecurityUser::class)->hash($value);

                    memzero($value);

                    return $hashed;
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('password_constraints', [])
            ->setDefault('empty_data', null)
            ->setDefault('command_factory', static fn (CommandDto $data, array $model) => new ChangePassword($model['id'], $data->fields['password']))
            ->setAllowedTypes('password_constraints', [Constraint::class . '[]', Constraint::class])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'change_user_password';
    }

    public function getParent(): ?string
    {
        return MessageFormType::class;
    }
}
