<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action;

use ParkManager\UI\Web\Response\TwigResponse;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Annotation\Route;

final class FormExampleAction
{
    #[Route(path: '/form-example', name: 'park_manager.form_example', methods: ['GET', 'POST'])]
    public function __invoke(FormFactoryInterface $formFactory): TwigResponse
    {
        $form = $formFactory->createBuilder()
            ->add('username', TextType::class, ['help' => 'Get notified when a candidate applies for a job.'])
            ->add('password', PasswordType::class, ['help' => 'Get notified when a candidate applies for a job.'])
            ->add('price', MoneyType::class, ['help' => 'Get notified when a candidate applies for a job.'])
            ->add('price2', MoneyType::class, ['currency' => 'USD', 'help' => 'Get notified when a candidate applies for a job.'])
            ->add('percent', PercentType::class, ['help' => 'Get notified when a candidate applies for a job.'])
            ->add('website', UrlType::class, ['help' => 'Get notified when a candidate applies for a job.'])
            ->add('textarea', TextareaType::class, ['help' => 'Get notified when a candidate applies for a job.'])
            ->add('date', DateType::class, ['help' => 'Get notified when a candidate applies for a job.'])
            ->add('time', TimeType::class, ['with_seconds' => true, 'help' => 'Get notified when a candidate applies for a job.'])
            ->add('datetime', DateTimeType::class, ['help' => 'Get notified when a candidate applies for a job.'])
            ->add('dateinterval', DateIntervalType::class, ['with_hours' => true, 'with_minutes' => true, 'with_seconds' => true, 'help' => 'Get notified when a candidate applies for a job.'])
            ->add('select', ChoiceType::class, ['choices' => array_flip(range('a', 'f')), 'expanded' => true, 'help' => 'Get notified when a candidate applies for a job.'])
            ->add('select2', ChoiceType::class, ['choices' => array_flip(range('g', 'n')), 'expanded' => true, 'multiple' => true, 'help' => 'Get notified when a candidate applies for a job.'])
            ->add('choice', ChoiceType::class, ['choices' => array_flip(range('g', 'n')), 'expanded' => false, 'multiple' => true, 'help' => 'Get notified when a candidate applies for a job.'])
            ->add('yes-no', CheckboxType::class, ['help' => 'Get notified when a candidate applies for a job.'])
            ->add('submit', ButtonType::class)
            ->getForm()
        ;

        /** @var FormInterface $child */
        foreach ($form as $child) {
            if ($child->getName() === 'submit') {
                continue;
            }

            $child->addError(new FormError('Oh boy that value looks wrong.'));
        }

        $form->addError(new FormError('Errors, errors everywhere.'));
        $form->addError(new FormError('Errors, errors everywhere (2).'));

        return new TwigResponse('form_example.html.twig', $form);
    }
}
