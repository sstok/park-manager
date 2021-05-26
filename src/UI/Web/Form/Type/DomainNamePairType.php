<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type;

use ParkManager\Domain\DomainName\DomainNamePair;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\Length;

final class DomainNamePairType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // The maximum label length is 63, the suffix is validated separate.

        $builder
            ->setDataMapper($this)
            ->add('name', TextType::class,
                [
                    'label' => 'label.name',
                    'help' => 'help.domain_name_name',
                    'attr' => [
                        'autocomplete' => 'off',
                        'autocorrect' => 'off',
                        'autocapitalize' => 'off',
                        'maxlength' => 63,
                    ],
                    'constraints' => new Length(max: 63),
                ]
            )
            ->add('suffix', TextType::class, ['label' => 'label.domain_suffix', 'help' => 'help.domain_name_suffix'])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'domain_name_pair';
    }

    /**
     * @param \RecursiveIteratorIterator $forms
     */
    public function mapDataToForms($viewData, iterable $forms): void
    {
        if ($viewData === null) {
            return;
        }

        if (! $viewData instanceof DomainNamePair) {
            throw new UnexpectedTypeException($viewData, DomainNamePair::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */
        $forms['name']->setData($viewData->name);
        $forms['suffix']->setData($viewData->tld);
    }

    /**
     * @param \RecursiveIteratorIterator $forms
     */
    public function mapFormsToData(iterable $forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */
        $name = $forms['name']->getData();
        $suffix = $forms['suffix']->getData();

        if ($name === null || $suffix === null) {
            $viewData = null;

            return;
        }

        $viewData = new DomainNamePair($name, $suffix);
    }
}
