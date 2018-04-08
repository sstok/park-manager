<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\UserBundle\Form\Handler;

use Hostnet\Component\FormHandler\HandlerConfigInterface;
use Hostnet\Component\FormHandler\HandlerTypeInterface;
use ParkManager\Bundle\UserBundle\Form\Type\ConfirmPasswordResetType;
use ParkManager\Component\ApplicationFoundation\Command\CommandBus;
use ParkManager\Component\User\Model\Command\ConfirmUserPasswordReset;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class ConfirmPasswordResetFormHandler implements HandlerTypeInterface
{
    private $requestStack;
    private $flashBag;
    private $translator;
    private $urlGenerator;
    private $passwordEncoder;
    private $commandBus;
    private $loginRoute;

    public function __construct(
        RequestStack $requestStack,
        FlashBagInterface $flashBag,
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
        PasswordEncoderInterface $passwordEncoder,
        CommandBus $commandBus,
        string $loginRoute
    ) {
        $this->commandBus = $commandBus;
        $this->flashBag = $flashBag;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->passwordEncoder = $passwordEncoder;
        $this->requestStack = $requestStack;
        $this->loginRoute = $loginRoute;
    }

    public function configure(HandlerConfigInterface $config): void
    {
        $config->setType(ConfirmPasswordResetType::class);
        $config->setOptions(function (array $data) {
            return ['token' => $data['token'], 'action' => $this->requestStack->getCurrentRequest()->getRequestUri()];
        });

        $config->onSuccess(function (array $data, FormInterface $form) {
            $this->commandBus->handle(
                new ConfirmUserPasswordReset(
                    $data['token'],
                    $this->passwordEncoder->encodePassword($form->get('password')->getData(), '')
                )
            );
            $this->flashBag->add('info', $this->translator->trans('flash.password_reset_accepted'));

            return new RedirectResponse($this->urlGenerator->generate($this->loginRoute));
        });
    }
}
