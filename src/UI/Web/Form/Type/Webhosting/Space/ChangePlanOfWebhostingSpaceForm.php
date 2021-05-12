<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Webhosting\Space;

use ParkManager\Application\Command\Webhosting\Constraint\AssignConstraintsToSpace;
use ParkManager\Application\Command\Webhosting\Constraint\AssignPlanToSpace;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\UI\Web\Form\Type\MessageFormType;
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
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
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
            $data = $event->getData()['fields'];

            if ($data['plan'] !== null && ! $data['no_link_plan']) {
                /** @var Plan $plan */
                $plan = $data['plan'];
                /** @var Constraints $constraints */
                $constraints = $data['constraints'];

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
                'command_factory' => static fn (array $fields, Space $space): object => $fields['no_link_plan'] || $fields['plan'] === null ?
                    new AssignConstraintsToSpace($space->id, $fields['constraints']) :
                    AssignPlanToSpace::withConstraints($fields['plan']->id, $space->id),
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
