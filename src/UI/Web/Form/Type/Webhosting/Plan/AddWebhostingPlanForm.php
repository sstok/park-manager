<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Webhosting\Plan;

use ParkManager\Application\Command\Webhosting\Constraint\CreatePlan;
use ParkManager\Domain\Webhosting\Constraint\PlanId;
use ParkManager\UI\Web\Form\Type\MessageFormType;
use ParkManager\UI\Web\Form\Type\Webhosting\Constraint\WebhostingConstraintsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AddWebhostingPlanForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('constraints', WebhostingConstraintsType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('disable_entity_mapping', true)
            ->setDefault(
                'command_factory',
                static fn (array $fields) => new CreatePlan(
                    PlanId::create(),
                    $fields['constraints'],
                    []
                )
            )
        ;
    }

    public function getParent(): string
    {
        return MessageFormType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'add_webhosting_plan_form';
    }
}
