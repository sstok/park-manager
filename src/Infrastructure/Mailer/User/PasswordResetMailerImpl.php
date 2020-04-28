<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Mailer\User;

use ParkManager\Application\Mailer\User\PasswordResetMailer;
use ParkManager\Domain\EmailAddress;
use Rollerworks\Component\SplitToken\SplitToken;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface as UrlGenerator;

final class PasswordResetMailerImpl implements PasswordResetMailer
{
    /** @var MailerInterface */
    private $mailer;

    /** @var UrlGenerator */
    private $urlGenerator;

    public function __construct(MailerInterface $mailer, UrlGenerator $urlGenerator)
    {
        $this->mailer = $mailer;
        $this->urlGenerator = $urlGenerator;
    }

    public function send(EmailAddress $emailAddress, SplitToken $splitToken): void
    {
        $email = (new TemplatedEmail())
            ->to($emailAddress->toMimeAddress())
            ->textTemplate('emails/user/security/password_reset.twig')
            ->context([
                'url' => $this->urlGenerator->generate(
                    'park_manager.security_confirm_password_reset',
                    ['token' => $splitToken->token()->getString()],
                    UrlGenerator::ABSOLUTE_URL
                ),
                'expiration_date' => $splitToken->getExpirationTime(),
            ]);

        $this->mailer->send($email);
    }
}
