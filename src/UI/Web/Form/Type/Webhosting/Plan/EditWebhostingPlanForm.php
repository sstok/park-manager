<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Webhosting\Plan;

use ParkManager\Application\Command\Webhosting\Constraint\UpdatePlan;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\UI\Web\Form\Type\MessageFormType;
use ParkManager\UI\Web\Form\Type\Webhosting\Constraint\WebhostingConstraintsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EditWebhostingPlanForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('constraints', WebhostingConstraintsType::class)
            ->add('updateLinked', CheckboxType::class, [
                'required' => false,
                'label' => 'webhosting.plan.edit.sync_label',
                'getter' => static fn (): bool => false, // Unmapped, but we still need the data.
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('command_factory', static fn (array $fields, Plan $plan) => new UpdatePlan(
            $plan->id,
            $fields['constraints'],
            metadata: [],
            updateLinkedSpaces: $fields['updateLinked'],
        ));
    }

    public function getParent(): string
    {
        return MessageFormType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'edit_webhosting_plan_form';
    }
}
