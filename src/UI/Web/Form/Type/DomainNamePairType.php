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
use Traversable;

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
     * @param Traversable<FormInterface> $forms
     */
    public function mapDataToForms($viewData, Traversable $forms): void
    {
        if ($viewData === null) {
            return;
        }

        if (! $viewData instanceof DomainNamePair) {
            throw new UnexpectedTypeException($viewData, DomainNamePair::class);
        }

        /** @var FormInterface[] $fields */
        $fields = iterator_to_array($forms);

        $fields['name']->setData($viewData->name);
        $fields['suffix']->setData($viewData->tld);
    }

    /**
     * @param Traversable<FormInterface> $forms
     */
    public function mapFormsToData(Traversable $forms, &$viewData): void
    {
        /** @var FormInterface[] $fields */
        $fields = iterator_to_array($forms);

        $name = $fields['name']->getData();
        $suffix = $fields['suffix']->getData();

        if ($name === null || $suffix === null) {
            $viewData = null;

            return;
        }

        $viewData = new DomainNamePair($name, $suffix);
    }
}
