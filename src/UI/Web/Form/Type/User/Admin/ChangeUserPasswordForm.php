<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\User\Admin;

use ParkManager\Application\Command\User\ChangeUserPassword;
use ParkManager\UI\Web\Form\Model\CommandDto;
use ParkManager\UI\Web\Form\Type\Security\ChangePasswordType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ChangeUserPasswordForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('temporary', CheckboxType::class, ['label' => 'label.mark_temp_password', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(
            'command_factory',
            static fn (CommandDto $data, array $model) => new ChangeUserPassword(
                $model['id'],
                $data->fields['password'],
                $data->fields['temporary']
            )
        );
    }

    public function getParent(): string
    {
        return ChangePasswordType::class;
    }
}
