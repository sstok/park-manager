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
use ParkManager\Application\Command\Webhosting\Constraint\AssignConstraintsToSpace;
use ParkManager\Application\Command\Webhosting\Constraint\AssignPlanToSpace;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\UI\Web\Form\Type\Webhosting\Constraint\WebhostingConstraintsType;
use ParkManager\UI\Web\Form\Type\Webhosting\Plan\WebhostingPlanSelector;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ChangePlanOfWebhostingSpaceForm extends AbstractType
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plan', WebhostingPlanSelector::class, [
                'required' => false,
                'placeholder' => 'label.placeholder_custom',
            ])
            ->add('no_link_plan', CheckboxType::class, [
                'label' => 'label.webhosting_plan.no_plan_link',
                'help' => 'help.webhosting.no_plan_link',
                'required' => false,
                'getter' => static fn (Space $space): bool => $space->plan === null,
            ])
            ->add('constraints', WebhostingConstraintsType::class, [
                'required' => false,
                'help_html' => true,
                'help' => 'help.webhosting.space_constraints',
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (PostSubmitEvent $event): void {
            $fields = $event->getData()->fields;

            if ($fields['plan'] !== null && ! $fields['no_link_plan']) {
                $plan = $fields['plan'];
                \assert($plan instanceof Plan);
                $constraints = $fields['constraints'];
                \assert($constraints instanceof Constraints);

                if (! $constraints->equals($plan->constraints)) {
                    $message = $this->translator->trans('webhosting_space.plan_constraints_mismatch', [], 'validators');

                    $event->getForm()->get('plan')->addError(new FormError($message, 'webhosting_space.plan_constraints_mismatch'));
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'help' => 'webhosting.space.change_plan.help',
                'help_html' => true,
                'command_factory' => static fn (CommandDto $data, Space $space): object => $data->fields['no_link_plan'] || $data->fields['plan'] === null ?
                    new AssignConstraintsToSpace($space->id, $data->fields['constraints']) :
                    AssignPlanToSpace::withConstraints($data->fields['plan']->id, $space->id),
            ]);
    }

    public function getBlockPrefix(): string
    {
        return 'webhosting_space_change_plan';
    }

    public function getParent(): string
    {
        return MessageFormType::class;
    }
}
