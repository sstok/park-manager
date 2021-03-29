<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type;

use ParkManager\Domain\ByteSize;
use ParkManager\UI\Web\Form\DataTransformer\ByteSizeToArrayTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ByteSizeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addModelTransformer(new ByteSizeToArrayTransformer())
            ->add('value', TextType::class, ['label' => 'label.value', 'empty_data' => 1])
            ->add('unit', ChoiceType::class, [
                'choices' => [
                    'byte_size.byte' => 'byte',
                    'byte_size.kib' => 'kib',
                    'byte_size.mib' => 'mib',
                    'byte_size.gib' => 'gib',
                ],
                'preferred_choices' => 'byte',
                'label' => 'label.unit',
                'help' => 'help.byte_size_unit',
            ]);

        if ($options['allow_infinite']) {
            $builder->add('isInf', CheckboxType::class, [
                'label' => 'byte_size.inf',
                'help' => 'help.byte_size_inf',
                'required' => false,
            ]);
        } else {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (PreSetDataEvent $event) use ($options): void {
                /** @var ByteSize|null $data */
                $data = $event->getData();

                if ($data !== null && $data->isInf()) {
                    $event->setData($options['infinite_replacement']);
                }
            });
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'allow_infinite' => true,
            'infinite_replacement' => new ByteSize(1, 'b'),
            'error_bubbling' => false,
            'by_reference' => false,
            // If initialized with a ByteSize object, FormType initializes
            // this option to "ByteSize". Since the internal, normalized
            // representation is not ByteSize, but an array, we need to unset
            // this option.
            'data_class' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'byte_size';
    }
}
