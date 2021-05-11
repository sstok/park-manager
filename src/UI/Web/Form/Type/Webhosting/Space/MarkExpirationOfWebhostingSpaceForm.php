<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Webhosting\Space;

use Carbon\CarbonImmutable;
use ParkManager\Application\Command\Webhosting\Space\ExpireSpaceOn;
use ParkManager\Application\Command\Webhosting\Space\MarkSpaceForRemoval;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\UI\Web\Form\Type\ConfirmationForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

final class MarkExpirationOfWebhostingSpaceForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('expirationDate', DateType::class, [
                'label' => 'label.expiration_date',
                'constraints' => [new Range(min: $min = new CarbonImmutable('today'))],
                'widget' => 'single_text',
                'attr' => [
                    'min' => $min->format('Y-m-d'),
                ],
                'input' => 'datetime_immutable',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('required_value')
            ->setDefault(
                'command_factory',
                static fn (array $fields, Space $model): object => CarbonImmutable::instance($fields['expirationDate'])->isCurrentDay() ? new MarkSpaceForRemoval($model->id) : new ExpireSpaceOn($model->id, $fields['expirationDate']),
        );
    }

    public function getBlockPrefix(): string
    {
        return 'webhosting_space_removal_marking';
    }

    public function getParent(): string
    {
        return ConfirmationForm::class;
    }
}
