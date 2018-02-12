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
use ParkManager\Bundle\UserBundle\Form\Type\RequestPasswordResetType;
use ParkManager\Component\User\Model\Command\RequestUserPasswordReset;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class RequestPasswordResetFormHandler implements HandlerTypeInterface
{
    private $commandBus;
    private $flashBag;
    private $translator;
    private $urlGenerator;
    private $loginRoute;

    public function __construct(
        FlashBagInterface $flashBag,
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
        CommandBus $commandBus,
        string $loginRoute
    ) {
        $this->commandBus = $commandBus;
        $this->flashBag = $flashBag;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->loginRoute = $loginRoute;
    }

    public function configure(HandlerConfigInterface $config): void
    {
        $config->setType(RequestPasswordResetType::class);
        $config->onSuccess(function (array $data) {
            $this->commandBus->handle(new RequestUserPasswordReset($data['email']));
            $this->flashBag->add('info', $this->translator->trans('flash.password_reset_send'));

            return new RedirectResponse($this->urlGenerator->generate($this->loginRoute));
        });
    }
}
