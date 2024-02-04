<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\User\Admin;

use Lifthill\Bridge\Web\Form\Model\CommandDto;
use Lifthill\Bridge\Web\Form\Type\MessageFormType;
use ParkManager\Application\Command\User\ChangeEmailAddress;
use ParkManager\Application\Command\User\RequestEmailAddressChange;
use ParkManager\Domain\User\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ChangeUserEmailAddressForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, ['transform_to_model' => true, 'label' => 'label.email'])
            ->add('require_confirm', CheckboxType::class, [
                'label' => 'label.requires_confirm',
                'help' => 'help.user_management.email_requires_confirm',
                'required' => false,
                'getter' => static fn (): bool => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('command_factory', static function (CommandDto $data, User $model): object {
            if ($data->fields['require_confirm']) {
                return new RequestEmailAddressChange($model->id, $data->fields['email']);
            }

            return new ChangeEmailAddress($model->id, $data->fields['email']);
        });
    }

    public function getParent(): ?string
    {
        return MessageFormType::class;
    }
}
