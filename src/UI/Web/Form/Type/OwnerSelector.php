<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type;

use ParkManager\Application\Service\CombinedResultSet;
use ParkManager\Domain\Organization\Organization;
use ParkManager\Domain\Organization\OrganizationRepository;
use ParkManager\Domain\Owner;
use ParkManager\Domain\OwnerId;
use ParkManager\Domain\OwnerRepository;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class OwnerSelector extends AbstractType
{
    public function __construct(
        private UserRepository $userRepository,
        private OrganizationRepository $organizationRepository,
        private OwnerRepository $ownerRepository,
        private TranslatorInterface $translator
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(
            new CallbackTransformer(
                static function ($value) {
                    if ($value === null) {
                        return null;
                    }

                    if (! $value instanceof Owner) {
                        throw new UnexpectedTypeException($value, Owner::class);
                    }

                    return $value->getLinkedEntity();
                },
                function ($value) {
                    if ($value === null) {
                        return null;
                    }

                    /** @var Organization|User $value */
                    if (! \is_object($value)) {
                        throw new UnexpectedTypeException($value, 'object');
                    }

                    return $this->ownerRepository->get(OwnerId::fromString($value->id->toString()));
                }
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'label.owner',
            'choice_label' => [$this, 'getLabel'],
            'group_by' => [$this, 'getGroup'],
            'resultset' => function (Options $options, $value) {
                $resultSets = [
                    $this->organizationRepository->all(),
                    $this->userRepository->all(),
                ];

                return new CombinedResultSet(...$resultSets);
            },
            'choice_vary' => [$this->ownerRepository::class],
            'choice_translation_domain' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'user_selector';
    }

    public function getParent(): string
    {
        return EntityType::class;
    }

    public function getLabel(User | Organization $owner): string
    {
        if ($owner instanceof User) {
            return sprintf(
                '%s (%s)',
                $owner->displayName,
                $owner->email->canonical,
            );
        }

        if ($owner->isInternal()) {
            return $owner->name;
        }

        return sprintf('%s (%s)', $owner->name, $owner->id->toString());
    }

    public function getGroup(User | Organization $owner): string
    {
        // This method is called for every entity, keeping a local cache of the translations
        // provides a better performance.
        static $userTrans, $orgInternalTrans, $orgUsersTrans;

        $userTrans ??= $this->translator->trans('label.users');
        $orgInternalTrans ??= $this->translator->trans('label.internal_organizations');
        $orgUsersTrans ??= $this->translator->trans('label.user_organizations');

        if ($owner instanceof User) {
            return $userTrans;
        }

        return $owner->isInternal() ? $orgInternalTrans : $orgUsersTrans;
    }
}
