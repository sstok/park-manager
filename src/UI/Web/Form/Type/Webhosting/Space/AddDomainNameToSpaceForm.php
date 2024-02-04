<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Webhosting\Space;

use Lifthill\Bridge\Web\Form\Type\DomainNamePairType;
use Lifthill\Bridge\Web\Form\Type\MessageFormType;
use ParkManager\Application\Command\DomainName\AddDomainNameToSpace;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AddDomainNameToSpaceForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', DomainNamePairType::class, [
                'label' => 'label.domain_name',
                'error_bubbling' => false,
            ])
            ->add('primary', CheckboxType::class, ['label' => 'primary', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('space')
            ->setAllowedTypes('space', SpaceId::class)
            ->setDefault('disable_entity_mapping', true)
            ->setDefault(
                'command_factory',
                static fn (array $fields, FormInterface $form) => new AddDomainNameToSpace($fields['name'], $form->getConfig()->getOption('space'), $fields['primary'])
            );
    }

    public function getParent(): string
    {
        return MessageFormType::class;
    }
}
