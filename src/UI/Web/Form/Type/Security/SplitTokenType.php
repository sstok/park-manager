<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Security;

use ParkManager\UI\Web\Form\DataTransformer\SplitTokenToStringTransformer;
use Rollerworks\Component\SplitToken\SplitToken;
use Rollerworks\Component\SplitToken\SplitTokenFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Throwable;

final class SplitTokenType extends AbstractType
{
    public function __construct(private SplitTokenFactory $splitTokenFactory)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Ensure that the Model data is always SplitToken object.
        // If it's invalid map to an form error.

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $data = $event->getData();

            if ($data === null || $data instanceof SplitToken) {
                return;
            }

            if (! \is_string($data)) {
                $data = '[invalid]';
            }

            try {
                $event->setData($this->splitTokenFactory->fromString($data));
            } catch (Throwable $e) {
                $form = $event->getForm();
                $config = $form->getConfig();

                $form->addError(
                    new FormError(
                        message: $config->getOption('invalid_message'),
                        messageParameters: $config->getOption('invalid_message_parameters'),
                        cause: $e
                    )
                );
            }
        });

        $builder->addViewTransformer(new SplitTokenToStringTransformer($this->splitTokenFactory));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('invalid_message', 'invalid_split_token');
        $resolver->setDefault('error_bubbling', true);
        $resolver->setDefault('data_class', null);
    }

    public function getParent(): ?string
    {
        return HiddenType::class;
    }
}
