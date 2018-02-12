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
use League\Tactician\CommandBus;
use ParkManager\Bundle\UserBundle\Form\Type\ChangePasswordType;
use ParkManager\Component\User\Model\Command\ChangeUserPassword;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class ChangePasswordFormHandler implements HandlerTypeInterface
{
    private $tokenStorage;
    private $requestStack;
    private $flashBag;
    private $translator;
    private $urlGenerator;
    private $passwordEncoder;
    private $commandBus;
    private $redirectRoute;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
        FlashBagInterface $flashBag,
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
        PasswordEncoderInterface $passwordEncoder,
        CommandBus $commandBus,
        string $redirectRoute
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->commandBus = $commandBus;
        $this->flashBag = $flashBag;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->passwordEncoder = $passwordEncoder;
        $this->requestStack = $requestStack;
        $this->redirectRoute = $redirectRoute;
    }

    public function configure(HandlerConfigInterface $config): void
    {
        $config->setType(ChangePasswordType::class);
        $config->setOptions(['action' => $this->requestStack->getCurrentRequest()->getRequestUri()]);

        $config->onSuccess(function (array $data) {
            $this->commandBus->handle(
                new ChangeUserPassword(
                    $this->tokenStorage->getToken()->getUsername(),
                    $this->passwordEncoder->encodePassword($data['new_password'], '')
                )
            );
            $this->flashBag->add('info', $this->translator->trans('flash.user_password_changed'));

            return new RedirectResponse($this->urlGenerator->generate($this->redirectRoute));
        });
    }
}
