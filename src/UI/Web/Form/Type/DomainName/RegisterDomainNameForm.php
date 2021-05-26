<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\DomainName;

use ParkManager\Application\Command\DomainName\AddDomainName;
use ParkManager\Domain\DomainName\DomainNameId;
use ParkManager\UI\Web\Form\Type\DomainNamePairType;
use ParkManager\UI\Web\Form\Type\MessageFormType;
use ParkManager\UI\Web\Form\Type\OwnerSelector;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RegisterDomainNameForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', DomainNamePairType::class, [
                'label' => 'label.domain_name',
                'error_bubbling' => false,
            ])
            ->add('owner', OwnerSelector::class, ['label' => 'label.owner'])
        ;
    }

    public function getParent(): string
    {
        return MessageFormType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('disable_entity_mapping', true)
            ->setDefault(
                'command_factory',
                static fn ($fields) => new AddDomainName(DomainNameId::create(), $fields['owner']->id, $fields['name'])
            )
        ;
    }
}
