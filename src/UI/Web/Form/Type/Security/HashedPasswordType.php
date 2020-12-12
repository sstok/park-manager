<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Security;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\SubmitEvent;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;

final class HashedPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::SUBMIT, static function (SubmitEvent $event): void {
            $encoder = $event->getForm()->getConfig()->getOption('algorithm');
            $value = $event->getData()['password'];

            if ($value === null) {
                return;
            }

            if (! \is_string($value)) {
                throw new UnexpectedTypeException($value, 'string');
            }

            $encodePassword = $encoder($value);
            \sodium_memzero($value);

            $event->setData($encodePassword);
        });

        $passwordOptions = $options['password_options'] + ['required' => $options['required']];
        $passwordOptions['attr'] = \array_merge(
            $passwordOptions['attr'] ?? [],
            [
                'autocomplete' => 'off',
                'autocorrect' => 'off',
                'autocapitalize' => 'off',
                'spellcheck' => 'false',
            ]
        );

        if ($options['password_confirm']) {
            $builder->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'password_not_the_same',
                'first_options' => ['label' => 'label.password', 'constraints' => $options['password_constraints']],
                'second_options' => ['label' => 'label.password2'],
            ] + $passwordOptions);
        } else {
            $builder->add('password', PasswordType::class, $passwordOptions + ['constraints' => $options['password_constraints']]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['algorithm']);
        $resolver->setDefaults([
            'password_options' => [],
            'password_confirm' => false,
            'password_constraints' => [],
            'constraints' => static function (Options $options, $value): void {
                if (! empty($value)) {
                    throw new InvalidOptionsException('Setting the "constraints" option for "' . self::class . '" is not possible. Use the "password_constraints" option instead.');
                }
            },
        ]);
        $resolver->setAllowedTypes('algorithm', 'callable');
        $resolver->setAllowedTypes('password_options', ['array']);
        $resolver->setAllowedTypes('password_confirm', ['bool']);
        $resolver->setAllowedTypes('password_constraints', ['array', Constraint::class]);
    }
}
