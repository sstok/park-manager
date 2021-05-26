<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Webhosting\Subdomain;

use ParkManager\Application\Command\Webhosting\SubDomain\EditSubDomain;
use ParkManager\Domain\Webhosting\SubDomain\SubDomain;
use ParkManager\Infrastructure\Validator\Constraints\X509CertificateBundle;
use Symfony\Component\Form\Event\PostSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EditSubDomainType extends SubDomainType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->addEventListener(FormEvents::POST_SET_DATA, static function (PostSetDataEvent $event): void {
            $model = $event->getData()['model'];
            \assert($model instanceof SubDomain);

            if ($model->tlsCert) {
                $event->getForm()->add('removeTLS', CheckboxType::class, ['data' => false, 'label' => 'label.remove_tls', 'help' => 'help.remove_tls', 'getter' => static fn () => false]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('model_class', SubDomain::class);
        $resolver->setDefault('command_factory', static function (array $form, SubDomain $model) {
            $command = new EditSubDomain(
                $model->id,
                $form['root_domain']->id,
                $form['name'],
                $form['homeDir'],
                $form['config'] ?? [] // To be done in the future
            );

            if ($form['tlsInfo'] !== null) {
                $tlsInformation = $form['tlsInfo'];
                \assert($tlsInformation instanceof X509CertificateBundle);
                $command->andTLSInformation($tlsInformation->certificate, $tlsInformation->privateKey, $tlsInformation->caList);
            } elseif ($form['removeTLS'] ?? false) {
                $command->removeTLSInformation();
            }

            return $command;
        });
    }

    public function getBlockPrefix(): string
    {
        return 'edit_sub_domain';
    }
}
