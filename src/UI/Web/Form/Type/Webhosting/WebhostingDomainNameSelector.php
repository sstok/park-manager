<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Webhosting;

use Lifthill\Bridge\Web\Form\Type\EntityType;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

final class WebhostingDomainNameSelector extends AbstractType
{
    public function __construct(private DomainNameRepository $domainNameRepository) {}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('space_id');
        $resolver->setAllowedTypes('space_id', [SpaceId::class]);

        $resolver
            ->setDefaults([
                'label' => 'label.domain_name',
                'choice_vary' => static fn (Options $options) => [$options['space_id']],
                'choice_label' => static fn (DomainName $domainName): string => $domainName->toString(),
                'preferred_choices' => static fn (DomainName $domainName) => [$domainName->primary],
                'resultset' => fn (Options $options) => $this->domainNameRepository->allFromSpace($options['space_id']),
                'constraints' => [new NotNull()],
            ]);
    }

    public function getBlockPrefix(): string
    {
        return 'space_domain_name_selector';
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
