<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Form\Type\Plan;

use ParkManager\Bundle\WebhostingBundle\Plan\ConstraintChecker;
use ParkManager\Bundle\WebhostingBundle\Plan\ConstraintExceeded;
use Rollerworks\Bundle\MessageBusFormBundle\Type\MessageFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PlanConstrainedFormType extends AbstractType
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var ConstraintChecker */
    private $constraintChecker;

    public function __construct(TranslatorInterface $translator, ConstraintChecker $constraintChecker)
    {
        $this->translator        = $translator;
        $this->constraintChecker = $constraintChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            try {
                $options['plan_validator']($this->constraintChecker, $event->getData());
            } catch (ConstraintExceeded $e) {
                $event->getForm()->addError(
                    new FormError(
                        $this->translator->trans($e->getTranslatorId(), $e->getTranslationParams(), 'messages'),
                        $e->getTranslatorId(),
                        $e->getTranslationParams(),
                        null,
                        $e
                    )
                );
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['plan_validator']);
        $resolver->setAllowedTypes('plan_validator', ['callable']);
    }

    public function getParent(): ?string
    {
        return MessageFormType::class;
    }
}
