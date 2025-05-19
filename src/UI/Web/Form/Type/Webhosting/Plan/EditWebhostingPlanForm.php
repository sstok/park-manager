<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Webhosting\Plan;

use Lifthill\Bridge\Web\Form\Model\CommandDto;
use Lifthill\Bridge\Web\Form\Type\MessageFormType;
use ParkManager\Application\Command\Webhosting\Constraint\UpdatePlan;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\UI\Web\Form\DataTransformer\LocalizedLabelCollectionTransformer;
use ParkManager\UI\Web\Form\Type\Webhosting\Constraint\WebhostingConstraintsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EditWebhostingPlanForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('default_label', TextType::class, [
                'label' => 'label.name',
                'getter' => static fn (Plan $plan): string => $plan->labels['_default'] ?? '',
            ])
            ->add('localized_labels', CollectionType::class, [
                'entry_type' => WebhostingPlanLabel::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => static fn (array $data) => mb_trim($data['value']) === '',
                'getter' => static function (Plan $plan): array {
                    $labels = $plan->labels;
                    unset($labels['_default']); // Don't include '_default' in initial form collection.

                    return $labels;
                },
                'block_prefix' => 'webhosting_localized_labels',
                'label' => 'label.localized_names',
                'help' => 'help.localized_names',
            ])
            ->add('constraints', WebhostingConstraintsType::class)
            ->add('updateLinked', CheckboxType::class, [
                'required' => false,
                'label' => 'webhosting.plan.edit.sync_label',
                'getter' => static fn (): bool => false, // Unmapped, but we still need the data.
            ]);

        $builder->get('localized_labels')->addModelTransformer(new LocalizedLabelCollectionTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(
            'command_factory',
            static fn (CommandDto $data, Plan $plan) => new UpdatePlan(
                $plan->id,
                $data->fields['constraints'],
                metadata: [],
                labels: ['_default' => $data->fields['default_label']] + $data->fields['localized_labels'],
                updateLinkedSpaces: $data->fields['updateLinked'],
            )
        );
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
