<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Search;

use Rollerworks\Component\Search\Field\AbstractFieldTypeExtension;
use Rollerworks\Component\Search\Field\OrderFieldType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OrderDirectionTranslationTypeExtension extends AbstractFieldTypeExtension
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'view_label' => ['ASC' => $this->translator->trans('order_asc'), 'DESC' => $this->translator->trans('order_desc')],
        ]);
    }

    public function getExtendedType(): string
    {
        return OrderFieldType::class;
    }
}
