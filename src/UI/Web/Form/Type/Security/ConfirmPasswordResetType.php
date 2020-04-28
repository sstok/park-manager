<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\Type\Security;

use ParkManager\Application\Command\User\ConfirmPasswordReset;
use ParkManager\Domain\Exception\PasswordResetTokenNotAccepted;
use ParkManager\UI\Web\Form\Type\MessageFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Validator\Constraint;
use Symfony\Contracts\Translation\TranslatorInterface as Translator;

final class ConfirmPasswordResetType extends AbstractType
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reset_token', SplitTokenType::class, [
                'invalid_message' => 'password_reset.invalid_token',
                'invalid_message_parameters' => [
                    '{reset_url}' => $this->urlGenerator->generate('park_manager.security_request_password_reset'),
                ],
            ])
            ->add('password', SecurityUserHashedPasswordType::class, [
                'required' => true,
                'password_confirm' => true,
                'label' => false,
                'password_constraints' => $options['password_constraints'],
            ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['token_invalid'] = false;

        foreach ($form->getErrors() as $error) {
            if ($error instanceof FormError && $error->getOrigin()->getName() === 'reset_token') {
                $view->vars['token_invalid'] = true;

                break;
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('password_constraints', [])
            ->setDefault('disable_entity_mapping', true)
            ->setDefault('command_factory', static function (array $data) {
                return new ConfirmPasswordReset($data['reset_token'], $data['password']);
            })
            ->setDefault('exception_mapping', [
                PasswordResetTokenNotAccepted::class => function (PasswordResetTokenNotAccepted $e, $translator, FormInterface $form) {
                    $arguments = [
                        '{reset_url}' => $this->urlGenerator->generate('park_manager.security_request_password_reset'),
                    ];

                    if ($e->storedToken() === null) {
                        return new FormError('password_reset.no_token', null, $arguments, null, $e);
                    }

                    return new FormError('password_reset.invalid_token', null, $arguments, null, $e);
                },
                DisabledException::class => static function (DisabledException $e, Translator $translator) {
                    return new FormError('password_reset.access_disabled', null, [], null, $e);
                },
            ])
            ->setAllowedTypes('password_constraints', ['array', Constraint::class]);
    }

    public function getBlockPrefix(): string
    {
        return 'confirm_user_password_reset';
    }

    public function getParent(): ?string
    {
        return MessageFormType::class;
    }
}
