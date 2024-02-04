<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Mailer\User;

use Lifthill\Component\Common\Domain\Model\EmailAddress;
use ParkManager\Application\Mailer\User\EmailAddressChangeRequestMailer;
use ParkManager\Infrastructure\Mailer\TemplatedEmail;
use Rollerworks\Component\SplitToken\SplitToken;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as UrlGenerator;

final class EmailAddressChangeRequestMailerImp implements EmailAddressChangeRequestMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGenerator $urlGenerator
    ) {}

    public function send(EmailAddress $newAddress, SplitToken $splitToken): void
    {
        $email = (new TemplatedEmail())
            ->to($newAddress->toMimeAddress())
            ->textTemplate('emails/user/confirm_email_address_change.twig')
            ->context([
                'url' => $this->urlGenerator->generate('park_manager.confirm_email_address_change', ['token' => $splitToken->token()], UrlGenerator::ABSOLUTE_URL),
                'expiration_date' => $splitToken->getExpirationTime(),
            ]);

        $this->mailer->send($email);
    }
}
