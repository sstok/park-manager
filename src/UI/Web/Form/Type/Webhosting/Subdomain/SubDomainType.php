<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Webhosting\Subdomain;

use Lifthill\Bridge\Web\Form\Type\MessageFormType;
use ParkManager\Infrastructure\Validator\Constraints\DirectoryPath;
use ParkManager\UI\Web\Form\Type\PEMCertificateType;
use ParkManager\UI\Web\Form\Type\Webhosting\WebhostingDomainNameSelector;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\AtLeastOneOf;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\Hostname;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Base FormType for SubDomain adding/modifying.
 */
abstract class SubDomainType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('root_domain', WebhostingDomainNameSelector::class, ['space_id' => $options['space_id'], 'property_path' => 'host'])
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new AtLeastOneOf([
                        new Hostname(['requireTld' => false]),
                        new EqualTo('@'), // primary/root
                    ]),
                ],
            ])
            ->add('homeDir', TextType::class, [
                'constraints' => [new DirectoryPath()],
                'help' => 'help.homedir',
            ])
            ->add('tlsInfo', PEMCertificateType::class, ['requires_private_key' => true, 'getter' => static fn () => null]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('space_id');
    }

    public function getParent(): string
    {
        return MessageFormType::class;
    }
}
