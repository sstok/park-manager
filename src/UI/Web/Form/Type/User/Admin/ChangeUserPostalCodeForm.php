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
use ParkManager\Application\Command\User\ChangePostalCode;
use ParkManager\Domain\User\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ChangeUserPostalCodeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('postalCode', TextType::class, ['label' => 'label.postal_code']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('command_factory', static fn (CommandDto $data, User $model): object => new ChangePostalCode($model->id, $data->fields['postalCode']));
    }

    public function getParent(): ?string
    {
        return MessageFormType::class;
    }
}
