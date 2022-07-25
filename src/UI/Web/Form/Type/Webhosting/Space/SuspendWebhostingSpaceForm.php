<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Webhosting\Space;

use ParkManager\Application\Command\Webhosting\Space\MarkSpaceAccessAsSuspended;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\Space\SuspensionLevel;
use ParkManager\UI\Web\Form\Model\CommandDto;
use ParkManager\UI\Web\Form\Type\MessageFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SuspendWebhostingSpaceForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('level', ChoiceType::class, [
            'label' => 'label.level',
            'property_path' => 'accessSuspended',
            'choices' => [
                'label.webhosting_suspension_level.access_limited' => SuspensionLevel::ACCESS_LIMITED,
                'label.webhosting_suspension_level.access_restricted' => SuspensionLevel::ACCESS_RESTRICTED,
                'label.webhosting_suspension_level.locked' => SuspensionLevel::LOCKED,
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(
            'command_factory',
            static fn (CommandDto $data, Space $model): object => new MarkSpaceAccessAsSuspended($model->id, $data->fields['level']),
        );
    }

    public function getBlockPrefix(): string
    {
        return 'webhosting_space_suspension';
    }

    public function getParent(): string
    {
        return MessageFormType::class;
    }
}
