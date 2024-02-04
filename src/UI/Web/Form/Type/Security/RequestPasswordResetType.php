<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Security;

use Lifthill\Bridge\Web\Form\Type\MessageFormType;
use ParkManager\Application\Command\User\RequestPasswordReset;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class RequestPasswordResetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'label.email',
                'constraints' => [new NotBlank(), new Email(['mode' => Email::VALIDATION_MODE_STRICT])],
            ]);
    }

    public function getBlockPrefix(): string
    {
        return 'request_user_password_reset';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('disable_entity_mapping', true);
        $resolver->setDefault('command_factory', static fn (array $data) => new RequestPasswordReset($data['email']));
    }

    public function getParent(): ?string
    {
        return MessageFormType::class;
    }
}
