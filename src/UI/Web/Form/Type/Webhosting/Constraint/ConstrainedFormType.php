<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Webhosting\Constraint;

use ParkManager\Domain\Webhosting\Constraint\Exception\ConstraintExceeded;
use ParkManager\UI\Web\Form\Type\MessageFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ConstrainedFormType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options): void {
            try {
                $options['constraints_validator']($event->getData());
            } catch (ConstraintExceeded $e) {
                $event->getForm()->addError(
                    new FormError(
                        $this->translator->trans($e->getTranslatorId(), $e->getTranslationArgs(), 'messages'),
                        $e->getTranslatorId(),
                        $e->getTranslationArgs(),
                        null,
                        $e
                    )
                );
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['constraints_validator']);
        $resolver->setAllowedTypes('constraints_validator', ['callable']);
    }

    public function getParent(): ?string
    {
        return MessageFormType::class;
    }
}
