<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Webhosting\Space;

use Lifthill\Bridge\Web\Form\Model\CommandDto;
use Lifthill\Bridge\Web\Form\Type\MessageFormType;
use ParkManager\Application\Command\Webhosting\Space\TransferSpaceToOwner;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\UI\Web\Form\Type\OwnerSelector;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class TransferWebhostingSpaceForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('owner', OwnerSelector::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(
            'command_factory',
            static fn (CommandDto $data, Space $model): object => new TransferSpaceToOwner($model->id, $data->fields['owner']->id),
        );
    }

    public function getBlockPrefix(): string
    {
        return 'webhosting_space_transfer';
    }

    public function getParent(): string
    {
        return MessageFormType::class;
    }
}
