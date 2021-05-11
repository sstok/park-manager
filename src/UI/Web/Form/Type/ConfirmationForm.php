<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

final class ConfirmationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['required_value']) {
            $builder->add('required_value', TextType::class, [
                'mapped' => true,
                'getter' => static fn (): string => '',
                'label' => 'label.value',
                'constraints' => new Callback([
                    'callback' => static function ($value, ExecutionContextInterface $context) use ($options): void {
                        if (! \preg_match('/^\s*(' . \preg_quote($options['required_value'], '/') . ')\s*$/i', $value)) {
                            $context
                                ->buildViolation('value_does_not_match_expected_value')
                                ->setParameter('{{ value }}', $value)
                                ->setParameter('{{ required_value }}', $options['required_value'])
                                ->setInvalidValue($value)
                                ->addViolation();
                        }
                    },
                ]),
                'attr' => [
                    'autocomplete' => 'off',
                    'autocorrect' => 'off',
                    'autocapitalize' => 'off',
                ],
            ]);
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['confirmation_title'] = $options['confirmation_title'];
        $view->vars['confirmation_message'] = $options['confirmation_message'];
        $view->vars['confirmation_label'] = $options['confirmation_label'];
        $view->vars['required_value'] = $options['required_value'];
        $view->vars['cancel_route'] = $options['cancel_route'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('required_value', null)
            ->setRequired(
                [
                    'confirmation_title',
                    'confirmation_message',
                    'confirmation_label',
                    'cancel_route',
                    'command_factory',
                ]
            )
            ->setAllowedTypes('confirmation_title', ['string', TranslatableInterface::class])
            ->setAllowedTypes('confirmation_message', ['string', TranslatableInterface::class])
            ->setAllowedTypes('confirmation_label', ['string', TranslatableInterface::class])
            ->setAllowedTypes('cancel_route', ['string', 'array'])
            ->setAllowedTypes('required_value', ['null', 'string'])
            ->setNormalizer(
                'confirmation_message',
                static function (Options $options, $value) {
                    if ($options['required_value'] !== null && ! $value instanceof TranslatableInterface) {
                        $value = new TranslatableMessage($value, ['required_value' => $options['required_value']]);
                    }

                    return $value;
                }
            )
            ->setNormalizer(
                'cancel_route',
                static function (Options $options, $value) {
                    if (\is_string($value)) {
                        return ['name' => $value, 'arguments' => []];
                    }

                    if (! isset($value['name'], $value['arguments'])) {
                        throw new InvalidOptionsException(
                            \sprintf(
                                'The "cancel_route" option must be either a string or array with keys "name" and "arguments", but got array with the following key(s): "%s".',
                                \implode('", "', \array_keys($value))
                            )
                        );
                    }

                    return $value;
                }
            );
    }

    public function getBlockPrefix(): string
    {
        return 'confirmation_form';
    }

    public function getParent(): string
    {
        return MessageFormType::class;
    }
}
