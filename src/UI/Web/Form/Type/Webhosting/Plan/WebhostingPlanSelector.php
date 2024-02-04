<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Webhosting\Plan;

use Lifthill\Bridge\Web\Form\Type\EntityType;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Constraint\PlanRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WebhostingPlanSelector extends AbstractType
{
    public function __construct(private PlanRepository $planRepository) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'label.plan',
            'resultset' => $this->planRepository->all(),
            'choice_label' => static fn (Plan $plan): string => $plan->getLabel(\Locale::getDefault()),
            'choice_vary' => [$this->planRepository::class, \Locale::getDefault()],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'webhosting_plan_selector';
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
