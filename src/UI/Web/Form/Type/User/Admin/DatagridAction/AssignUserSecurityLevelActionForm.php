<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\User\Admin\DatagridAction;

use Lifthill\Component\Datagrid\Extension\Form\DatagridActionForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class AssignUserSecurityLevelActionForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('level', ChoiceType::class, [
                'expanded' => true,
                'choices' => [
                    'user_role.super_admin' => 'ROLE_SUPER_ADMIN',
                    'user_role.admin' => 'ROLE_ADMIN',
                    'user_role.user' => 'ROLE_USER',
                ],
            ])
            ->add('submit', SubmitType::class);
    }

    public function getBlockPrefix(): string
    {
        return 'user_security_level';
    }

    public function getParent(): string
    {
        return DatagridActionForm::class;
    }
}
