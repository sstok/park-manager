<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Webhosting\Space;

use ParkManager\Application\Command\Webhosting\Space\RegisterWebhostingSpace;
use ParkManager\Domain\DomainName\Exception\CannotAssignDomainNameWithDifferentOwner;
use ParkManager\Domain\DomainName\Exception\DomainNameAlreadyInUse;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\UI\Web\Form\Type\DomainNamePairType;
use ParkManager\UI\Web\Form\Type\MessageFormType;
use ParkManager\UI\Web\Form\Type\OwnerSelector;
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

final class RegisterWebhostingSpaceForm extends AbstractType
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // XXX In the Vue.js component:
        //
        // -- the "domain_name" field should allow a suggestions for domain-names currently owned by the selected owner.
        // -- the "constraints" field should be populated with the selected plan (if any);
        //    changing the plan should ask if the constraints should be updated.
        //    changing the constraints will automatically check 'no_link_plan'.

        $builder
            ->add('owner', OwnerSelector::class)
            ->add('domain_name', DomainNamePairType::class, [
                'label' => 'label.primary_domain_name',
                'error_bubbling' => false,
                'help' => 'help.webhosting.space_register_domain',
            ])
            ->add('plan', WebhostingPlanSelector::class, [
                'required' => false,
                'placeholder' => 'label.placeholder_custom',
            ])
            ->add('no_link_plan', CheckboxType::class, [
                'label' => 'label.webhosting_plan.no_plan_link',
                'help' => 'help.webhosting.no_plan_link',
                'required' => false,
            ])
            ->add('constraints', WebhostingConstraintsType::class, [
                'required' => false,
                'help_html' => true,
                'help' => 'help.webhosting.space_constraints',
            ])
        ;

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (PostSubmitEvent $event): void {
            $data = $event->getData();

            if ($data['plan'] !== null && ! $data['no_link_plan']) {
                $plan = $data['plan'];
                \assert($plan instanceof Plan);
                $constraints = $data['constraints'];
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
            ->setDefaults(
                [
                    'help' => 'help.webhosting.space_register',
                    'disable_entity_mapping' => true,
                    'command_factory' => static fn (array $fields): object => $fields['no_link_plan'] || $fields['plan'] === null ?
                        RegisterWebhostingSpace::withCustomConstraints(
                            SpaceId::create()->toString(),
                            $fields['domain_name'],
                            (string) $fields['owner'],
                            $fields['constraints']
                        ) :
                        RegisterWebhostingSpace::withPlan(
                            SpaceId::create()->toString(),
                            $fields['domain_name'],
                            (string) $fields['owner'],
                            $fields['plan']->id->toString()
                        ),
                    'exception_mapping' => [
                        DomainNameAlreadyInUse::class => 'domain_name',
                        CannotAssignDomainNameWithDifferentOwner::class => 'domain_name',
                    ],
                ]
            )
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'webhosting_space_form';
    }

    public function getParent(): string
    {
        return MessageFormType::class;
    }
}
