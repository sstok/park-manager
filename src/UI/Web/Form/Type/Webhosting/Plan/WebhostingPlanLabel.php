<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Webhosting\Plan;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WebhostingPlanLabel extends AbstractType
{
    public function __construct(private array $acceptedLocales = [])
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('value', TextType::class, ['label' => 'label.name'])
            ->add('locale', LocaleType::class, [
                'label' => 'label.locale',
                'choice_filter' => fn ($locale) => \in_array($locale, $this->acceptedLocales, true),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('label', false);
    }

    public function getBlockPrefix(): string
    {
        return 'webhosting_plan_label';
    }
}
