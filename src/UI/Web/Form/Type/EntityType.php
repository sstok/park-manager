<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type;

use Doctrine\Common\Collections\Collection;
use ParkManager\Domain\ResultSet;
use ParkManager\UI\Web\Form\ChoiceList\ResultSetChoiceLoader;
use Symfony\Bridge\Doctrine\Form\DataTransformer\CollectionToArrayTransformer;
use Symfony\Bridge\Doctrine\Form\EventListener\MergeDoctrineCollectionListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\ChoiceList\Factory\Cache\AbstractStaticOption;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Alternative implementation of {@see \Symfony\Bridge\Doctrine\Form\Type\EntityType}
 * to allow usage of application specific Repositories.
 */
final class EntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['multiple'] && \interface_exists(Collection::class)) {
            $builder
                ->addEventSubscriber(new MergeDoctrineCollectionListener())
                ->addViewTransformer(new CollectionToArrayTransformer(), true);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choiceLoader = function (Options $options) {
            if ($options['choices'] !== null) {
                return null;
            }

            return ChoiceList::loader(
                $this,
                new ResultSetChoiceLoader($options['resultset']),
                // an array containing anything that "changes" the loader
                $options['choice_vary']
            );
        };

        // 'resultset_loader' must a callable that provides a ResetSet using a repository
        // 'choice_vary' must be an array!
        $resolver
            ->setRequired(['choice_label', 'resultset', 'choice_vary'])
            ->setDefaults([
                'choices' => null,
                'choice_loader' => $choiceLoader,
                'choice_name' => [__CLASS__, 'createChoiceName'],
                'choice_value' => [__CLASS__, 'createChoiceValue'],
                'choice_translation_domain' => false,
            ])
            ->setAllowedTypes('resultset', ResultSet::class)
            ->setAllowedTypes('choice_vary', 'array');

        $resolver->setNormalizer('choice_name', function (Options $options, $value) {
            if ($value instanceof AbstractStaticOption) {
                return $value;
            }

            return ChoiceList::fieldName($this, $value, $options['choice_vary']);
        });

        $resolver->setNormalizer('choice_value', function (Options $options, $value) {
            if ($value instanceof AbstractStaticOption) {
                return $value;
            }

            return ChoiceList::value($this, $value, $options['choice_vary']);
        });

        $resolver->setNormalizer('choice_label', function (Options $options, $value) {
            if ($value instanceof AbstractStaticOption || ! \is_callable($value)) {
                return $value;
            }

            return ChoiceList::label($this, $value, $options['choice_vary']);
        });

        $resolver->setNormalizer('choice_attr', function (Options $options, $value) {
            if ($value instanceof AbstractStaticOption || ! \is_callable($value)) {
                return $value;
            }

            return ChoiceList::attr($this, $value, $options['choice_vary']);
        });

        $resolver->setNormalizer('preferred_choices', function (Options $options, $value) {
            if ($value instanceof AbstractStaticOption || ! \is_callable($value)) {
                return $value;
            }

            return ChoiceList::preferred($this, $value, $options['choice_vary']);
        });

        $resolver->setNormalizer('group_by', function (Options $options, $value) {
            if ($value instanceof AbstractStaticOption || ! \is_callable($value)) {
                return $value;
            }

            return ChoiceList::groupBy($this, $value, $options['choice_vary']);
        });

        // We don't apply caching for filtering, as this unneeded for entities.
    }

    /**
     * @internal This method is public to be usable as callback. It should not
     *           be used in user code.
     */
    public static function createChoiceName(?object $choice): ?string
    {
        if ($choice === null) {
            return null;
        }

        // ID's are UUID (hex) formatted, so we can't guarantee it doesn't start numeric
        return 'choice_' . \str_replace('-', '_', (string) $choice->id);
    }

    /**
     * @internal This method is public to be usable as callback. It should not
     *           be used in user code.
     */
    public static function createChoiceValue(?object $choice): ?string
    {
        if ($choice === null) {
            return null;
        }

        return (string) $choice->id;
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
