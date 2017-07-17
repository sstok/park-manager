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

namespace ParkManager\Bundle\UserBundle\Service;

use ParkManager\Component\Security\Token\SplitToken;
use ParkManager\Component\User\Model\Service\EmailAddressChangeConfirmationMailer;
use ParkManager\Component\User\Model\Service\PasswordResetMailer;
use Sylius\Component\Mailer\Sender\SenderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class EmailAddressChangeConfirmationSwiftMailer implements EmailAddressChangeConfirmationMailer
{
    private $sender;
    private $urlGenerator;
    private $confirmChangeRoute;

    public function __construct(SenderInterface $sender, UrlGeneratorInterface $urlGenerator, string $confirmChangeRoute)
    {
        $this->sender = $sender;
        $this->urlGenerator = $urlGenerator;
        $this->confirmChangeRoute = $confirmChangeRoute;
    }

    public function send(string $emailAddress, SplitToken $splitToken, \DateTimeImmutable $tokenExpiration): void
    {
        $this->sender->send(
            'park_manager.user.confirm_email_address_change',
            [$emailAddress],
            [
                'url' => $this->urlGenerator->generate($this->confirmChangeRoute, ['token' => $splitToken->token()], UrlGeneratorInterface::ABSOLUTE_URL),
                'expiration_date' => $tokenExpiration,
            ]
        );
    }
}
