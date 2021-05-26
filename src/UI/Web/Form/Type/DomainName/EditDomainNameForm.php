<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\DomainName;

use ParkManager\Application\Command\DomainName\AssignDomainNameToOwner;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\UI\Web\Form\Type\MessageFormType;
use ParkManager\UI\Web\Form\Type\OwnerSelector;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EditDomainNameForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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
            ->setDefault(
                'command_factory',
                static fn (array $fields, DomainName $model) => new AssignDomainNameToOwner($model->id, $fields['owner']->id)
            )
        ;
    }
}
