<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Webhosting\Constraint;

use Lifthill\Bridge\Web\Form\Type\ByteSizeType;
use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Domain\Webhosting\Constraint\DBConstraints;
use ParkManager\Domain\Webhosting\Constraint\EmailConstraints;
use ParkManager\UI\Web\Form\DataMapper\WebhostingConstraintDataMapper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotEqualTo;

/**
 * Must be kept in sync with {@see Constraints}.
 */
final class WebhostingConstraintsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setDataMapper(new WebhostingConstraintDataMapper(Constraints::class))
            ->addEventListener(FormEvents::PRE_SET_DATA, static function (PreSetDataEvent $event): void {
                if ($event->getData() === null) {
                    $event->setData(new Constraints());
                }
            })
            ->add('monthlyTraffic', IntegerType::class, [
                'label' => 'label.monthly_traffic',
                'help' => 'help.webhosting.monthly_traffic',
                'constraints' => [new GreaterThanOrEqual(-1), new NotEqualTo(0)],
            ])
            ->add('storageSize', ByteSizeType::class, [
                'label' => 'label.storage_size',
                'help' => 'help.webhosting.total_space_storage_size',
            ])
            ->add($this->getEmailConstraintsForm($builder))
            ->add($this->getDBConstraintsForm($builder));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['label' => 'label.webhosting_constraints', 'data_class' => Constraints::class]);
    }

    private function getEmailConstraintsForm(FormBuilderInterface $builder): FormBuilderInterface
    {
        return $builder->create('email', FormType::class, [
            'label' => 'label.webhosting_plan.email_constraints',
            'help' => 'help.webhosting.email_constraints',
            'help_html' => true,
            'data_class' => EmailConstraints::class,
            'block_prefix' => 'webhosting_constraints_sub',
        ])
            ->setDataMapper(new WebhostingConstraintDataMapper(EmailConstraints::class))
            ->add('maxStorageSize', ByteSizeType::class, [
                'label' => 'label.webhosting_plan.email_max_storage_size',
            ])
            ->add('maximumAddressCount', IntegerType::class, [
                'label' => 'label.webhosting_plan.maximum_email_address_count',
                'constraints' => new GreaterThanOrEqual(-1),
            ])
            ->add('maximumMailboxCount', IntegerType::class, [
                'label' => 'label.webhosting_plan.maximum_emailbox_count',
                'constraints' => new GreaterThanOrEqual(-1),
            ])
            ->add('maximumForwardCount', IntegerType::class, [
                'label' => 'label.webhosting_plan.maximum_email_forward_count',
                'constraints' => new GreaterThanOrEqual(-1),
            ])
            ->add('spamFilterCount', IntegerType::class, [
                'label' => 'label.webhosting_plan.spam_filter_count',
                'constraints' => new GreaterThanOrEqual(-1),
            ])
            ->add('mailListCount', IntegerType::class, [
                'label' => 'label.webhosting_plan.email_list_count',
                'constraints' => new GreaterThanOrEqual(-1),
            ]);
    }

    private function getDBConstraintsForm(FormBuilderInterface $builder): FormBuilderInterface
    {
        return $builder->create('database', FormType::class, [
            'label' => 'label.webhosting_plan.database_constraints',
            'data_class' => DBConstraints::class,
            'block_prefix' => 'webhosting_constraints_sub',
        ])
            ->setDataMapper(new WebhostingConstraintDataMapper(DBConstraints::class))
            ->add('providedStorageSize', ByteSizeType::class, [
                'label' => 'label.webhosting_plan.database_provided_storage_size',
            ])
            ->add('maximumAmountPerType', IntegerType::class, [
                'label' => 'label.webhosting_plan.database_maximum_amount_per_type',
                'constraints' => new GreaterThanOrEqual(-1),
            ])
            ->add('enabledPgsql', CheckboxType::class, [
                'label' => 'label.webhosting_plan.database_enabled_pgsql',
                'block_prefix' => 'webhosting_constraints_checkbox',
                'required' => false,
            ])
            ->add('enabledMysql', CheckboxType::class, [
                'label' => 'label.webhosting_plan.database_enabled_mysql',
                'block_prefix' => 'webhosting_constraints_checkbox',
                'required' => false,
            ]);
    }

    public function getBlockPrefix(): string
    {
        return 'webhosting_constraints';
    }
}
