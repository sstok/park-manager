<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Mailer\Client;

use ParkManager\Bundle\CoreBundle\Model\EmailAddress;
use Rollerworks\Component\SplitToken\SplitToken;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as UrlGenerator;

final class EmailAddressChangeRequestMailerImp implements EmailAddressChangeRequestMailer
{
    /** @var MailerInterface */
    private $mailer;

    /** @var UrlGenerator */
    private $urlGenerator;

    public function __construct(MailerInterface $mailer, UrlGenerator $urlGenerator)
    {
        $this->mailer       = $mailer;
        $this->urlGenerator = $urlGenerator;
    }

    public function send(EmailAddress $newAddress, SplitToken $splitToken): void
    {
        $email = (new TemplatedEmail())
            ->to($newAddress->toMimeAddress())
            ->textTemplate('@ParkManagerCore/email/client/confirm_email_address_change.twig')
            ->context([
                'url' => $this->urlGenerator->generate('', ['token' => $splitToken->token()], UrlGenerator::ABSOLUTE_URL),
                'expiration_date' => $splitToken->getExpirationTime(),
            ]);

        $this->mailer->send($email);
    }
}
